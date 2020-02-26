<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

use App\PurchaseRequestApproval;
use App\PurchaseRequestSupplierDetails;
use App\PurchaseRequest;
use App\PurchaseRequestItems;
use App\Masterlist;
use App\Supplier;
use App\User;

class PurchasesSupplierController extends Controller
{

  public function getPrList()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;
    $pageSize = request()->pageSize;
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
    $prList = $q->limit($limit)->get();
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
      'id')
      ->groupBy('prsd_pr_id');

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $approval = DB::table('psms_prapprovaldetails')
      ->select('pra_prs_id',
        DB::raw('count(*) as approvalRequestCount'),
        DB::raw('sum(pra_approved = 1) as approveCount'),
        DB::raw('sum(pra_rejected = 1) as rejectCount'))
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
      'supplier.id as supplier',
      'supplier.sd_supplier_name as supplierLabel',
      'pr_prnum as prNum',
      'jo_joborder as joNum',
      'po_ponum as poNum',
      'pr_date as date',
      'pr_remarks as remarks',
      'itemCount',
      DB::raw('IF( IFNULL(approvalRequestCount,0) > 0,
      IF( IFNULL(approveCount,0) = approvalRequestCount,"APPROVED", IF( IFNULL(rejectCount,0) > 0,"REJECTED","PENDING") ),
      "NO REQUEST" ) as status'),
       DB::raw('IFNULL(approvalRequestCount,0) as approvalRequestCount'),
       DB::raw('IFNULL(rejectCount,0) as rejectCount'),
       DB::raw('IFNULL(approveCount,0) as approveCount'),
    ]);
    $prList = $q->limit($limit)->get();
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
        'dateNeeded' => $item->pr->jo->jo_dateneeded,
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
      'message' => 'Recorda added'
    ]); 
  }
  //pr with price
  public function prWithPriceArray($pr){
    $status = 'NO REQUEST';
    $approvalReqCount = $pr->prpricing->prApproval()->count();
    $approvedCount = $pr->prpricing->prApproval()->where('pra_approved',1)->count();
    $rejectedCount = $pr->prpricing->prApproval()->where('pra_rejected',1)->count();
    if($pr->prpricing->prApproval()->count() > 0){
      $status = "PENDING";
      if($approvalReqCount == $approvedCount)
        $status = "APPROVED";
      if($rejectedCount > 0)
        $status = "REJECTED";
    }
    return array(
      'id' => $pr->id,
      'price_id' => $pr->prpricing->id,
      'supplier' => $pr->prpricing->supplier->id,
      'supplierLabel' => $pr->prpricing->supplier->sd_supplier_name,
      'prNum' => $pr->pr_prnum,
      'joNum' => $pr->jo->jo_joborder,
      'poNum' => $pr->jo->poitems->po->po_ponum,
      'date' => $pr->pr_date,
      'remarks' => $pr->pr_remarks,
      'itemCount' => $pr->pritems()->count(),
      'status' => $status,
      'approvalRequestCount' => $approvalReqCount,
      'approveCount' => $approvedCount,
      'rejectCount' => $rejectedCount,
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

    if($pr->prpricing
      ->prApproval()
      ->where('pra_approved',1)
      ->orWhere('pra_rejected',1)
      ->count() > 0){
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
  }

  public function getApprovalList($id){
    $prsd = PurchaseRequestSupplierDetails::findOrFail($id);
    $users = User::select('id','username')
      ->where('id', '!=', Auth()->user()->id)
      ->where('approval_pr', 1)
      ->get();

    $approvalList = $prsd->prApproval
      ->map(function($list){
        return $this->approvalArray($list);
      });
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
        'approver' => 'required|string|unique:psms_prapprovaldetails,pra_approver_userid,null,id,pra_prs_id,'.$request->price_id,
        'method' => 'required|string|in:LAN,ONLINE',
      ));
    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }
    $prSupplierDetails = PurchaseRequestSupplierDetails::findOrFail($request->price_id);
    $approvalReqCount = $prSupplierDetails->prApproval()->count();
    $approvedCount = $prSupplierDetails->prApproval()->where('pra_approved',1)->count();
    $rejectedCount = $prSupplierDetails->prApproval()->where('pra_rejected',1)->count();

    if(($approvalReqCount > 0) &&
      ( ($approvalReqCount == $approvedCount) || ($rejectedCount > 0)) ){
      return response()->json(['errors' => ['Cannot add more requests']],422);
    }

    $approval = new PurchaseRequestApproval();
    $user = User::findOrFail($request->approver);
    $key = $this->generateRandomString();

    $pr = $prSupplierDetails->pr;
    $approval->fill([
      'pra_prs_id' => $request->price_id,
      'pra_key' => $key,
      'pra_approver_userid' => $request->approver,
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

    $q = PurchaseRequestApproval::where('pra_approver_userid',$userId)
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
      $prList = $q->latest('created_at')->limit($limit)->get();

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

    if(Auth()->user()->id !== $request->pra_approver_userid)
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

    $pr = PurchaseRequestItems::whereHas('pr.prpricing', function($q) use ($request){
      $q->whereIn('id', $request->prsID);
    })
    ->get();

    return $pr;
  }

}
