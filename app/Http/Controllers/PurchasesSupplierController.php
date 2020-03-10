<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DB;
use Excel;
use PDF;
use Storage;
use File;
use Response;
use Carbon\Carbon;

use App\PurchaseRequestApproval;
use App\PurchaseOrderSupplier;
use App\PurchaseRequestSupplierDetails;
use App\PurchaseOrderSeries;
use App\PurchaseRequest;
use App\PurchaseRequestItems;
use App\Masterlist;
use App\Supplier;
use App\User;

class PurchasesSupplierController extends Controller
{

  public function getTitle($gender){
    if(strtolower($gender) == 'male')
      return 'mr.';
    else if(strtolower($gender) == 'female')
      return 'ms.';
    else return '';
  }

  public function printPurchaseOrder(Request $request,$id){
    $token = $request->bearerToken();
    $po = PurchaseOrderSupplier::findOrFail($id);
    $user = $po->user;
    $sd = $po->prprice()->first()->supplier;
    $getPr = PurchaseRequest::whereHas('prpricing.po', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->pluck('pr_prnum')
      ->toArray();

    $prNumber = implode(",", $getPr);
    $prItems = PurchaseRequestItems::whereHas('pr.prpricing.po', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->select(
        'id',
        DB::raw('IF(count(*) > 1,NULL,pri_code) as code'),
        'pri_mspecs as materialSpecification',
        'pri_uom as unit',
        'pri_unitprice as unitprice',
        'pri_deliverydate as deliveryDate',
        DB::raw('CAST(sum(pri_quantity) as int) as quantity')
      )
      ->groupBy('pri_mspecs', 'pri_uom', 'pri_unitprice')
      ->get();

    $poDetails = (object) array(
      'poNumber' => $po->spo_ponum,
      'currency' => $po->prprice()->first()->prsd_currency,
      'date' => $po->created_at->format('F d, Y'),
      'prNumber' => $prNumber,
      'supplierName' => $sd->sd_supplier_name,
      'address' => $sd->sd_address,
      'tin' => $sd->sd_tin,
      'attention' => $sd->sd_attention,
      'paymentTerms' => $sd->sd_paymentterms,
    );
    $preparedByName = NULL;
    $checkByName = NULL;
    $approvedByName = NULL;
    $preparedBySig = false;
    $prepareBySigFile = '';
    if($user){
      $preparedByName = strtoupper($this->getTitle($user->gender)." ".
        SUBSTR($user->firstname,0,1).$user->middleinitial." ".$user->lastname);
      $prepareBySigFile = $user->id.'/'.$user->signature;
      $preparedBySig = Storage::disk('local')
      ->exists('/users/signature/'.$prepareBySigFile);
    }
    $getOm = User::where('department','om')->where('position','Manager')->first();
    $getGm = User::where('department','gm')->where('position','Manager')->first();

    if($getOm){
      $checkByName = strtoupper($this->getTitle($getOm->gender)." ".
        SUBSTR($getOm->firstname,0,1).$getOm->middleinitial." ".$getOm->lastname);
    }

    if($getGm){
      $approvedByName = strtoupper($this->getTitle($getGm->gender)." ".
        SUBSTR($getGm->firstname,0,1).$getGm->middleinitial." ".$getGm->lastname);
    }

    $pdf =  PDF::loadView('psms.printPurchaseOrder', compact(
      'poDetails',
      'prItems',
      'preparedByName',
      'checkByName',
      'approvedByName',
      'preparedBySig',
      'prepareBySigFile',
      'token'
    ))->setPaper('a4','portrait');
    return $pdf->stream($po->spo_ponum);
  }

  public function getFileSignature(){
    $filepath = request()->has('filepath') ? request()->filepath : 'empty';
    $path = storage_path('app/users/signature/' . $filepath);
    if (!File::exists($path) || !Auth()->user()) {
      abort(404);
    }
    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);

    return $response;
  }

  public function printPR(Request $request,$id)
  {
    $token = $request->bearerToken();
    $prs = PurchaseRequestSupplierDetails::findOrFail($id);
    $details = array(
      'jo' => $prs->pr->jo->jo_joborder,
      'po' => $prs->pr->jo->poitems->po->po_ponum,
      'currency' => $prs->prsd_currency,
      'pr' => $prs->pr->pr_prnum,
      'date' => $prs->created_at->toFormattedDateString(),
    );
    $items = $prs->pr->pritems->map(function($item) use ($prs) {
      return array(
        'id' => $item->id,
        'code' => $item->pri_code,
        'materialSpecification' => $item->pri_mspecs,
        'unit' => $item->pri_uom,
        'quantity' => $item->pri_quantity,
        'unitPrice' => $item->pri_unitprice,
        'amount' => $item->pri_unitprice * $item->pri_quantity,
        'deliveryDate' => $item->pri_deliverydate,
        'supplier' => $prs->supplier->sd_supplier_name,
      );
    })->toArray();
    $prFileName = $prs->pr->pr_user_id.'/'.$prs->pr->user->signature;
    $prsFileName = $prs->prsd_user_id.'/'.$prs->user->signature;
    
    $prSignature = Storage::disk('local')
      ->exists('/users/signature/'.$prFileName);

    $prpriceSignature = Storage::disk('local')
      ->exists('/users/signature/'.$prsFileName);

    $isApproved = false;
    $approvalSig = false;
    $approvalFileName = '';
    $gmName = NULL;
    $gmSig = NULL;
    $gmSigExist = false;

    $approvalReq = $prs->prApproval;

    $getGm = User::where('department','gm')->where('position','Manager')->first();
    if($getGm){
      $gmName = strtoupper($this->getTitle($getGm->gender)." ".
        SUBSTR($getGm->firstname,0,1).$getGm->middleinitial." ".$getGm->lastname);

      $gmSig = $user->id.'/'.$user->signature;
      $gmSigExist = Storage::disk('local')
        ->exists('/users/signature/'.$prepareBySigFile);
    }

    if($approvalReq){
      if($approvalReq->pra_approved > 0){
        $isApproved = true;
        $approvalFileName = $approvalReq->pra_approver_id.'/'.
            $approvalReq->user->signature;
        $approvalSig = Storage::disk('local')
          ->exists('/users/signature/'.$approvalFileName);
      }
    }

    $pdf =  PDF::loadView('psms.printPurchaseRequest', compact(
      'items',
      'details',
      'prSignature',
      'prpriceSignature',
      'isApproved',
      'approvalSig',
      'prFileName',
      'prsFileName',
      'approvalFileName',
      'gmName',
      'gmSig',
      'gmSigExist',
      'token'
    ))->setPaper('a4','portrait');
    return $pdf->stream($prs->pr->pr_prnum);
  }

  public function getPrList()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;
    $prPrice = DB::table('psms_prsupplierdetails')
      ->select('prsd_pr_id',DB::raw('count(*) as hasSupplier'),
      'prsd_supplier_id')
      ->groupBy('prsd_pr_id');

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $itemsTbl = DB::table('prms_pritems')
      ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
      ->groupBy('pri_pr_id');

    $jo = DB::table('pjoms_joborder')
      ->select('id','jo_po_item_id','jo_joborder')
      ->groupBy('id');

    $poitem = DB::table('cposms_purchaseorderitem')
      ->select('id','poi_po_id')
      ->groupBy('id');

    $po = DB::table('cposms_purchaseorder')
      ->select('id','po_ponum')
      ->groupBy('id');

    $q = PurchaseRequest::has('jo.poitems.po')
        ->leftJoinSub($prPrice, 'prprice', function($join){
          $join->on('prms_prlist.id','=','prprice.prsd_pr_id');
        })
        ->leftJoinSub($itemsTbl, 'items', function($join){
          $join->on('prms_prlist.id','=','items.pri_pr_id');
        })
        ->leftJoinSub($jo, 'jo', function($join){
          $join->on('prms_prlist.pr_jo_id','=','jo.id');
        })
        ->leftJoinSub($poitem, 'poitem', function($join){
          $join->on('jo.jo_po_item_id','=','poitem.id');
        })
        ->leftJoinSub($po, 'po', function($join){
          $join->on('poitem.poi_po_id','=','po.id');
        });

    $q->select([
      'prms_prlist.id as id',
      DB::raw('IF(hasSupplier > 0,"W/ PRICE","NO PRICE") as status'),
      'pr_prnum as prNum',
      'jo_joborder as joNum',
      'po_ponum as poNum',
      'pr_date as date',
      'pr_remarks as remarks',
      'itemCount'
    ]);

    $q->where('pr_forPricing', 1);
    $prList = $q->orderBy('id','DESC')
      ->limit($limit)
      ->get();
    $prListLength = count($prList);

    return response()->json([
      'prListLength' => $prListLength,
      'prList' => $prList,
    ]);
  }

  public function getPrListWithPrice()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;

    $prPrice = DB::table('psms_prsupplierdetails')
      ->select('prsd_pr_id',DB::raw('count(*) as hasSupplier'),
      'prsd_supplier_id',
      'prsd_spo_id',
      'prsd_currency',
      'id')
      ->groupBy('prsd_pr_id');

    $suppPo = DB::table('psms_spurchaseorder')
      ->select('id', DB::raw('count(*) as hasPo'))
      ->groupBy('id');

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $approval = DB::table('psms_prapprovaldetails')
      ->select('pra_prs_id',
        DB::raw('count(*) as approvalRequestCount'),
        DB::raw('pra_approved as isApproved'),
        DB::raw('pra_rejected as isRejected'))
      ->groupBy('pra_prs_id');

    $itemsTbl = DB::table('prms_pritems')
      ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
      ->groupBy('pri_pr_id');

    $jo = DB::table('pjoms_joborder')
      ->select('id','jo_po_item_id','jo_joborder')
      ->groupBy('id');

    $poitem = DB::table('cposms_purchaseorderitem')
      ->select('id','poi_po_id')
      ->groupBy('id');

    $po = DB::table('cposms_purchaseorder')
      ->select('id','po_ponum')
      ->groupBy('id');

    $q = PurchaseRequest::has('prpricing')
      ->has('jo.poitems.po')
      ->leftJoinSub($prPrice, 'prprice', function($join){
        $join->on('prms_prlist.id','=','prprice.prsd_pr_id');
      })
      ->leftJoinSub($suppPo, 'spo', function($join){
        $join->on('spo.id','=','prprice.prsd_spo_id');
      })
      ->leftJoinSub($approval, 'approval', function($join){
        $join->on('approval.pra_prs_id','=','prprice.id');
      })
      ->leftJoinSub($supplier, 'supplier', function($join){
        $join->on('supplier.id','=','prprice.prsd_supplier_id');
      })
      ->leftJoinSub($itemsTbl, 'items', function($join){
        $join->on('prms_prlist.id','=','items.pri_pr_id');
      })
      ->leftJoinSub($jo, 'jo', function($join){
        $join->on('prms_prlist.pr_jo_id','=','jo.id');
      })
      ->leftJoinSub($poitem, 'poitem', function($join){
        $join->on('jo.jo_po_item_id','=','poitem.id');
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('poitem.poi_po_id','=','po.id');
      });

    $q->select([
      'prms_prlist.id as id',
      'prprice.id as price_id',
      'prprice.prsd_currency as currency',
      'supplier.id as supplier',
      'supplier.sd_supplier_name as supplierLabel',
      'pr_prnum as prNum',
      'jo_joborder as joNum',
      'po_ponum as poNum',
      'pr_date as date',
      'pr_remarks as remarks',
      'itemCount',
      DB::raw(' IF( IFNULL(hasPo,0) > 0, "WITH PO",
        IF( IFNULL(approvalRequestCount,0) > 0,
        IF( IFNULL(isApproved,0) > 0,"APPROVED", IF( IFNULL(isRejected,0) > 0,"REJECTED","PENDING") ),
        "NO REQUEST")
        ) as status'),
       DB::raw('IF(approvalRequestCount,true, false) as hasRequest'),
    ]);
    $prList = $q->orderBy('price_id','DESC')
      ->limit($limit)
      ->get();
    $prListLength = count($prList);

    return response()->json([
      'prPriceListLength' => $prListLength,
      'prPriceList' => $prList,
    ]);
  }

  public function getSupplier(){

    $supplier = Supplier::select('id', 'sd_supplier_name as supplierName')
                  ->orderBy('sd_supplier_name','ASC')
                  ->get();
    return response()->json([
      'supplierList' => $supplier,
    ]);

  }

  public function getPrInfo($id)
  {

    $pr = PurchaseRequest::findOrFail($id);
    $jo = $pr->jo;
    $prItems = array();

    $poJoDetails = array(
      'po' => $jo->poitems->po->po_ponum,
      'code' => $jo->poitems->poi_code,
      'itemDesc' => $jo->poitems->poi_itemdescription,
      'partNum' => $jo->poitems->poi_partnum,
      'poQty' => $jo->poitems->poi_quantity,
      'currency' => $jo->poitems->po->po_currency,
      'jo' => $jo->jo_joborder,
      'joQty' => $jo->jo_quantity,
      'dateNeeded' => $jo->jo_dateneeded,
    );

    foreach($pr->pritems as $item){

      $masterlist = Masterlist::where('m_code',$item->pri_code)->first();
      $costing = 'No match record';
      $budgetPrice = 'No match record';

      if($masterlist){
        $costing = $masterlist->m_supplierprice != null ? $masterlist->m_supplierprice : 'No Input';
        $budgetPrice = $masterlist->m_budgetprice != null ? $masterlist->m_budgetprice : 0;
      }

       array_push($prItems, array(
        'id' => $item->id,
        'code' => $item->pri_code,
        'mspecs' => $item->pri_mspecs,
        'unit' => $item->pri_uom,
        'quantity' => $item->pri_quantity,
        'unitPrice' => $item->pri_unitprice,
        'dateNeeded' => $item->pri_deliverydate != null
          ? $item->pri_deliverydate
          : $item->pr->jo->jo_dateneeded,
        'costing' => $costing,
        'budgetPrice' => $budgetPrice
      ));
    }

    return response()->json([
      'poJoDetails' => $poJoDetails,
      'prItems' => $prItems,
    ]);

  }

  public function getPriceForItems($prId,$supplierId){

    $pr = PurchaseRequest::findOrFail($prId);
    $prPricing = array();
    foreach($pr->pritems as $item){

      $price = PurchaseRequestItems::whereHas('pr.prpricing', function($q) use ($supplierId){
          $q->where('prsd_supplier_id', $supplierId);
        })
        ->where('pri_mspecs', $item->pri_mspecs)
        ->latest('id')
        ->first();

       array_push($prPricing, array(
        'id' => $item->id,
        'unitPrice' => $price ? $price->pri_unitprice : 0     
      ));
    }

    return response()->json([
      'prPricing' => $prPricing
    ]);
  }

  public function addPriceForItems(Request $request){
    $validator = Validator::make($request->all(),
      array(
        'id' => 'required|int',
        'prNum' => 'required|string',
        'supplier' => 'required',
        'currency' => 'required|string',
        'prItems' => 'array|min:1|required',
      ),[],
    [
      'prNum' => 'purchase request number',
      'prItems' => 'purchase request items',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }
    $pr = PurchaseRequest::findOrFail($request->id);
    if($pr->prpricing){
      return response()->json(['errors' => ['PR already have supplier & price']],422);
    }
    $pr->prpricing()->create([
      'prsd_supplier_id' => $request->supplier,
      'prsd_currency' => $request->currency,
      'prsd_user_id' => Auth()->user()->id,
    ]);

    foreach($request->prItems as $item){
      PurchaseRequestItems::findOrFail($item['id'])->update([
        'pri_unitprice' => $item['unitPrice'],
        'pri_deliverydate' => $item['dateNeeded'],
      ]);
    }
    $pr->refresh();
    $newPr = array(
      'id' => $pr->id,
      'status' => $pr->prpricing ? "W/ PRICE" : "NO PRICE",
      'prNum' => $pr->pr_prnum,
      'joNum' => $pr->jo->jo_joborder,
      'poNum' => $pr->jo->poitems->po->po_ponum,
      'date' => $pr->pr_date,
      'remarks' => $pr->pr_remarks,
      'itemCount' => $pr->pritems()->count(),
    );

    return response()->json([
      'newPr' => $newPr,
      'message' => 'Record added'
    ]); 
  }
  //pr with price
  public function prWithPriceArray($pr){
    $status = 'NO REQUEST';
    $approval = $pr->prpricing->prApproval;
    if($approval){
      $status = "PENDING";
      if($approval->pra_approved > 0)
        $status = "APPROVED";
      if($approval->pra_rejected > 0)
        $status = "REJECTED";
      if($pr->prpricing->po)
        $status = "WITH PO";
    }
    return array(
      'id' => $pr->id,
      'price_id' => $pr->prpricing->id,
      'currency' => $pr->prpricing->prsd_currency,
      'supplier' => $pr->prpricing->supplier->id,
      'supplierLabel' => $pr->prpricing->supplier->sd_supplier_name,
      'prNum' => $pr->pr_prnum,
      'joNum' => $pr->jo->jo_joborder,
      'poNum' => $pr->jo->poitems->po->po_ponum,
      'date' => $pr->pr_date,
      'remarks' => $pr->pr_remarks,
      'itemCount' => $pr->pritems()->count(),
      'status' => $status,
      'hasRequest' => $approval ? true : false,
    );
  }

  public function editPrWithPrice(Request $request,$id){
    $validator = Validator::make($request->all(),
      array(
        'id' => 'required|int',
        'prNum' => 'required|string',
        'supplier' => 'required',
        'currency' => 'required|string',
        'prItems' => 'array|min:1|required',
      ),[],
    [
      'prNum' => 'purchase request number',
      'prItems' => 'purchase request items',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }
    $pr = PurchaseRequest::findOrFail($id);

    if($pr->prpricing->prApproval &&
      ($pr->prpricing->prApproval->pra_approved == 1 ||
      $pr->prpricing->prApproval->pra_rejected == 1)){
      return response()->json(['errors' => ['Record not editable']],422);
    }
    $pr->prpricing()->update([
      'prsd_supplier_id' => $request->supplier,
      'prsd_currency' => $request->currency,
    ]);

    foreach($request->prItems as $item){
      PurchaseRequestItems::findOrFail($item['id'])->update([
        'pri_unitprice' => $item['unitPrice'],
        'pri_deliverydate' => $item['dateNeeded'],
      ]);
    }
    $pr->refresh();
    return response()->json([
      'newPr' => $this->prWithPriceArray($pr),
      'message' => 'Record updated'
    ]);
  }

  public function deletePriceOnPr($id){
    $prsd = PurchaseRequestSupplierDetails::findOrFail($id);
    $prsd->pr->prItems()->update([
      'pri_unitprice' => 0,
      'pri_deliverydate' => null,
    ]);
    $prsd->prApproval()->delete();
    $prsd->delete();
    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  public function approvalArray($list){
    if($list){
      return array(
        'id' => $list->id,
        'key' => $list->pra_key,
        'approver' => $list->pra_approver_user,
        'otherInfo' => $list->pra_otherinfo,
        'method' => $list->pra_approvalType,
        'isApproved' => $list->pra_approved,
        'isRejected' => $list->pra_rejected,
        'remarks' => $list->pra_remarks,
        'date' => $list->pra_date,
      );
    }else
      return (object)[];
  }

  public function getApprovalList($id){
    $prsd = PurchaseRequestSupplierDetails::findOrFail($id);
    $users = User::select('id','username')
      ->where('id', '!=', Auth()->user()->id)
      ->where('approval_pr', 1)
      ->get();

    $approvalList = $this->approvalArray($prsd->prApproval);
    // $approvalList = $prsd->prApproval
    //   ->map(function($list){
    //     return $this->approvalArray($prsd->prApproval);
    //   });
    return response()->json([
      'approvalList' => $approvalList,
      'approverList' => $users, 
    ]);
  }

  private function generateRandomString() {
    $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < 100; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
    return $random_string;
  }

  public function addApprovalRequest(Request $request){
    $validator = Validator::make($request->all(),
      array(
        'price_id' => 'required|int',
        'approver' => 'required|string|unique:psms_prapprovaldetails,pra_approver_id,null,id,pra_prs_id,'.$request->price_id,
        'method' => 'required|string|in:LAN,ONLINE',
      ));
    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }
    $prSupplierDetails = PurchaseRequestSupplierDetails::findOrFail($request->price_id);

    if($prSupplierDetails->prApproval){
      return response()->json(['errors' => ['Cannot add more requests']],422);
    }

    $approval = new PurchaseRequestApproval();
    $user = User::findOrFail($request->approver);
    $key = $this->generateRandomString();

    $pr = $prSupplierDetails->pr;
    $approval->fill([
      'pra_prs_id' => $request->price_id,
      'pra_key' => $key,
      'pra_approver_id' => $request->approver,
      'pra_approver_user' => $user->username,
      'pra_otherinfo' => $pr->jo->jo_joborder." - ". $pr->pr_prnum,
      'pra_approvalType' => $request->method,
    ]);
    $approval->save();
    return response()->json([
      'message' => 'Record added',
      'newApprovalRequest' => $this->approvalArray($approval),
      'newPr' => $this->prWithPriceArray($pr),
    ]);
  }

  public function deleteApprovalRequest($id){
    $approval = PurchaseRequestApproval::findOrFail($id);
    $prsd = PurchaseRequestSupplierDetails::findOrFail($approval->pra_prs_id);
    $approval->delete();
    $pr = $this->prWithPriceArray($prsd->pr);
    return response()->json([
      'message' => 'Record deleted',
      'newPr' => $pr,
    ]);
  }

  //approval on pr
  public function getPendingPrList(){
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;
    $userId = Auth()->user()->id;
    $pr = DB::table('prms_prlist')
      ->select('id','pr_jo_id','pr_prnum')
      ->groupBy('id');

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $itemsTbl = DB::table('prms_pritems')
      ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
      ->groupBy('pri_pr_id');

    $jo = DB::table('pjoms_joborder')
      ->select('id','jo_po_item_id','jo_joborder')
      ->groupBy('id');

    $poitem = DB::table('cposms_purchaseorderitem')
      ->select('id','poi_po_id')
      ->groupBy('id');

    $po = DB::table('cposms_purchaseorder')
      ->select('id','po_ponum')
      ->groupBy('id');

    $prPrice = DB::table('psms_prsupplierdetails')
      ->select('id','prsd_supplier_id','prsd_pr_id')
      ->groupBy('id');

    $q = PurchaseRequestApproval::where('pra_approver_id',$userId)
      ->leftJoinSub($prPrice, 'prprice', function($join){
        $join->on('psms_prapprovaldetails.pra_prs_id','=','prprice.id');
      })
      ->leftJoinSub($pr, 'pr', function($join){
        $join->on('pr.id','=','prprice.prsd_pr_id');
      })
      ->leftJoinSub($supplier, 'supplier', function($join){
        $join->on('supplier.id','=','prprice.prsd_supplier_id');
      })
      ->leftJoinSub($itemsTbl, 'items', function($join){
        $join->on('pr.id','=','items.pri_pr_id');
      })
      ->leftJoinSub($jo, 'jo', function($join){
        $join->on('pr.pr_jo_id','=','jo.id');
      })
      ->leftJoinSub($poitem, 'poitem', function($join){
        $join->on('jo.jo_po_item_id','=','poitem.id');
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('poitem.poi_po_id','=','po.id');
      });

      $q->select([
        DB::raw('IF(psms_prapprovaldetails.pra_approved > 0,"APPROVED",
        IF(psms_prapprovaldetails.pra_rejected > 0,"REJECTED", "PENDING")) as status'),
        'psms_prapprovaldetails.id',
        'prprice.id as priceId',
        'supplier.sd_supplier_name as supplier',
        'jo_joborder as joNum',
        'pr_prnum as prNum',
        'po_ponum as poNum',
        'psms_prapprovaldetails.created_at as created_at',
        'psms_prapprovaldetails.pra_remarks as remarks',
      ]);
      $prList = $q->latest('id')->limit($limit)->get();

      return response()->json([
        'prList' => $prList,
      ]);
  }

  public function getPrDetails($id){

    $prsd = PurchaseRequestSupplierDetails::findOrFail($id);
    $pr = $prsd->pr;
    $po = $pr->jo->poitems->po;
    $poitemId = $pr->jo->poitems->id;
    $prItems = array();
    $poItems = array();
    $poDetails = array(
      'poNumber' => $po->po_ponum,
      'customerName' => $po->customer->companyname,
      'poDate' => $po->po_date,
    );
    foreach($pr->pritems as $item){
      $masterlist = Masterlist::where('m_code',$item->pri_code)->first();
      $costing = 'No match record';
      $budgetPrice = 'No match record';

      if($masterlist){
        $costing = $masterlist->m_supplierprice != null ? $masterlist->m_supplierprice : 'No Input';
        $budgetPrice = $masterlist->m_budgetprice != null ? $masterlist->m_budgetprice : 0;
      }

       array_push($prItems, array(
        'id' => $item->id,
        'code' => $item->pri_code,
        'mspecs' => $item->pri_mspecs,
        'unit' => $item->pri_uom,
        'quantity' => $item->pri_quantity,
        'unitPrice' => $item->pri_unitprice,
        'amount' => $item->pri_quantity * $item->pri_unitprice,
        'dateNeeded' => $item->pr->jo->jo_dateneeded,
        'costing' => $costing,
        'budgetPrice' => $budgetPrice."(".number_format((((
          $budgetPrice - $item->pri_unitprice) / $budgetPrice) * 100),2,'.','')."%)" ,
      ));
    }

    foreach($po->poitems as $row){
      $totalAmt = $row->poi_unitprice * $row->poi_quantity;
      array_push($poItems, array(
        'id' => $row->id,
        'code' => $row->poi_code,
        'itemDescription' => $row->poi_itemdescription,
        'quantity' => $row->poi_quantity,
        'currency' => $row->po->po_currency,
        'unitPrice' => $row->poi_unitprice,
        'totalAmount' => strtoupper($po->po_currency) == 'USD' ? $totalAmt * 50 : $totalAmt,
        'isMatchItem' => $poitemId == $row->id ? true : false,
      ));
    }

    return response()->json([
      'prItems' => $prItems,
      'poDetails' => $poDetails,
      'poItems' => $poItems,
    ]);

  }

  public function approvedRejectRequest(Request $data, $id)
  {
    $request = PurchaseRequestApproval::findOrFail($id);

    if(Auth()->user()->id !== $request->pra_approver_id)
      return response()->json(['errors' => ['Permission denied']], 422);

    if($request->pra_approved > 0 || $request->pra_rejected > 0)
      return response()->json(['errors' => ['Request already approved or rejected'] ], 422); 

    if(strtolower($data->type) != 'approved' && strtolower($data->type) != 'reject')
      return response()->json(['errors' => ['Type not valid']], 422); 

    $request->fill([
      'pra_approved' => strtolower($data->type) == 'approved' ? 1 : 0,
      'pra_rejected' => strtolower($data->type) == 'rejected' ? 1 : 0,
      'pra_date' => date('Y-m-d'),
    ]);
    $request->save();
    $request->refresh();
    $status = "PENDING";
    if($request->pra_approved > 0)
      $status = "APPROVED";

    if($request->pra_rejected > 0)
        $status = "REJECTED";

    $updatedRequest = array(
      'status' => $status,
      'id' => $request->id,
      'priceId' => $request->prprice->id,
      'supplier' => $request->prprice->supplier->sd_supplier_name,
      'joNum' => $request->prprice->pr->jo->jo_joborder,
      'prNum' => $request->prprice->pr->pr_prnum,
      'poNum' => $request->prprice->pr->jo->poitems->po->po_ponum,
      'created_at' => $request->created_at,
      'remarks' => $request->pra_remarks,
    );
    
    return response()->json([
      'updatedRequest' => $updatedRequest,
      'message' => 'Record '.strtoupper($data->type)
    ]);
  }

  public function addRemarks(Request $data, $id)
  {
    $request = PurchaseRequestApproval::findOrFail($id);

    $request->update([
      'pra_remarks' => $data->remarks,
    ]);
    $request->refresh();
    $status = "PENDING";
    if($request->pra_approved > 0)
      $status = "APPROVED";

    if($request->pra_rejected > 0)
      $status = "REJECTED";

    $updatedRequest = array(
      'status' => $status,
      'id' => $request->id,
      'priceId' => $request->prprice->id,
      'supplier' => $request->prprice->supplier->sd_supplier_name,
      'joNum' => $request->prprice->pr->jo->jo_joborder,
      'prNum' => $request->prprice->pr->pr_prnum,
      'poNum' => $request->prprice->pr->jo->poitems->po->po_ponum,
      'created_at' => $request->created_at,
      'remarks' => $request->pra_remarks,
    );
    
    return response()->json([
      'updatedRequest' => $updatedRequest,
      'message' => 'Record updated',
    ]);
  }

  public function getAllDetailsForPr(Request $request){

    $validator = Validator::make($request->all(),
      [
        'prsID' => 'array|min:1|required',
      ],[],
      ['prsID' => 'Purchase request']
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $pritems = PurchaseRequestItems::whereHas('pr.prpricing', function($q) use ($request){
      $q->whereIn('id', $request->prsID);
    })
    ->get();

    $checkIfSameSupplier = $pritems->every(function($item) use ($pritems){
      return $item->pr->prpricing->prsd_supplier_id == $pritems[0]->pr->prpricing->prsd_supplier_id &&
        $item->pr->prpricing->prsd_currency == $pritems[0]->pr->prpricing->prsd_currency ;
    });

    if(!$checkIfSameSupplier)
      return response()-json(['errors' => ['You can only create purchase order with same supplier & currency'] ], 422);

    $sd = PurchaseRequestSupplierDetails::whereIn('id', $request->prsID)
      ->first()
      ->supplier;
    $prList = PurchaseRequestSupplierDetails::whereIn('id', $request->prsID)
      ->get()
      ->map(function($pr){
        return $pr->pr->pr_prnum;
      })
      ->toArray();

    $items = $pritems->map(function($item) {
      return array(
        'id' => $item->id,
        'code' => $item->pri_code,
        'materialSpecification' => $item->pri_mspecs,
        'unit' => $item->pri_uom,
        'quantity' => $item->pri_quantity,
        'unitprice' => $item->pri_unitprice,
        'deliveryDate' => $item->pri_deliverydate,
      );
    })->values();
    $supplierDetails = array(
      'supplierName' => $sd->sd_supplier_name,
      'address' => $sd->sd_address,
      'tin' => $sd->sd_tin,
      'attention' => $sd->sd_attention,
      'paymentTerms' => $sd->sd_paymentterms,
    );
    $series = PurchaseOrderSeries::first();
    $number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
    $poseries = $series->series_prefix.date('y'). "-".$number;

    return response()->json([
      'poSeries' => $poseries,
      'prNumbers' => implode(",",$prList),
      'supplierDetails' => $supplierDetails,
      'poItems' => $items,
    ]);
  }

  public function getPurchaseOrder()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;

    $joinQry = "SELECT
      prms_prlist.id,
      prsd.prsd_supplier_id as supplier_id,
      prsd.prsd_spo_id,
      prsd.prsd_currency as currency,
      GROUP_CONCAT(pr_prnum) as prnumbers,
      CAST(sum(itemCount) as int) as itemCount,
      CAST(sum(totalPrQuantity) as int) as totalPoQuantity,
      IFNULL(CAST(sum(invoiceDelivered) as int),0) as invoiceDelivered
      FROM prms_prlist
      LEFT JOIN psms_prsupplierdetails prsd 
      ON prsd.prsd_pr_id = prms_prlist.id
      LEFT JOIN (SELECT count(*) as itemCount,
      SUM(pri_quantity) as totalPrQuantity,pri_pr_id,id
      FROM prms_pritems
      GROUP BY pri_pr_id) pri
      ON pri.pri_pr_id = prms_prlist.id
      LEFT JOIN (SELECT SUM(ssi_receivedquantity) as invoiceDelivered,ssi_pritem_id 
      FROM psms_supplierinvoice
      WHERE ssi_receivedquantity > 0
      GROUP BY ssi_pritem_id) prsi
      ON prsi.ssi_pritem_id = pri.id
      GROUP BY prsd_spo_id";

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $itemsTbl = DB::table('prms_pritems')
      ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
      ->groupBy('pri_pr_id');

    $poList = PurchaseOrderSupplier::has('prprice.pr.jo.poitems.po')
      ->leftJoinSub($joinQry,'pr', function($join){
        $join->on('pr.prsd_spo_id','=','psms_spurchaseorder.id');
      })
      ->leftJoinSub($supplier,'supplier', function($join){
        $join->on('supplier.id','=','pr.supplier_id');
      })
      ->select(
        'psms_spurchaseorder.id',
        'sd_supplier_name as supplier',
        'spo_ponum as poNum',
        'prnumbers',
        'currency',
        'itemCount',
        Db::raw('IF(spo_sentToSupplier = 0 && invoiceDelivered < 1,
          "PENDING",
          IF(invoiceDelivered > 0,
            IF(invoiceDelivered >= totalPoQuantity,
              "DELIVERED",
              "PARTIAL"
            ),
            "SENT"
          )
        ) as status')
      )
      ->orderBy('id','DESC')
      ->limit($limit)
      ->get();

    return response()->json([
      'poList' => $poList,
    ]);
  }

  public function addPurchaseOrder(Request $request)
  {
    $validator = Validator::make($request->all(),
      [
        'poSeries' => 'required|string|min:1',
        'prsID' => 'array|min:1',
      ],[],
      ['prsID' => 'Purchase request']
    );

    $purchaseOrder = new PurchaseOrderSupplier();
    $purchaseOrder->fill([
      'spo_ponum' => $request->poSeries,
      'spo_user_id' => Auth()->user()->id,
      'spo_date' => date('Y-m-d'),
    ]);
    $purchaseOrder->save();
    PurchaseOrderSeries::first()
      ->update(['series_number' => DB::raw('series_number + 1')]); //update series
    $addedIds = array();
    foreach($request->prsID as $id)
    {
      $prs = PurchaseRequestSupplierDetails::findOrFail($id);
      if(!$prs->po){
        $prs->update([
          'prsd_spo_id' => $purchaseOrder->id,
        ]);
        array_push($addedIds, $id);
      }
    }

    return response()->json([
      'addedIDs' => $addedIds,
      'message' => 'Record added',
    ]);
  }

  public function cancelPurchaseOrder($id)
  {
    $po = PurchaseOrderSupplier::findOrFail($id); 
    $po->prprice()->update([
      'prsd_spo_id' => 0,
    ]);
    $po->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }
}
