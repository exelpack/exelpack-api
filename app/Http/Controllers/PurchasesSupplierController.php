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
use App\PurchaseOrderSupplierItems;
use App\PurchaseRequestSupplierDetails;
use App\PurchaseOrderSeries;
use App\PurchaseRequest;
use App\PurchaseRequestItems;
use App\SupplierInvoice;
use App\Masterlist;
use App\Supplier;
use App\User;
use App\JobOrder;
use App\PurchaseOrderItems;

use App\Exports\ExportSupplierPurchaseOrder;
use App\Exports\ExportSupplierPurchaseOrderItems;
use App\Exports\PurchasesReportExport;

class PurchasesSupplierController extends LogsController
{
  public function getTitle($gender){
    if(strtolower($gender) == 'male')
      return 'mr.';
    else if(strtolower($gender) == 'female')
      return 'ms.';
    else return '';
  }

  // exports
  public function exportPurchasesReport(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'date' => 'array|required|min:2|max:2',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()]);
    }

    return Excel::download(new PurchasesReportExport(...$request->date), 'purchasesreport.xlsx');
  }

  public function exportPurchaseOrder()
  {
    return Excel::download(new ExportSupplierPurchaseOrder, 'purchaseorder.xlsx');
  }

  public function exportPurchaseOrderItems()
  {
    return Excel::download(new ExportSupplierPurchaseOrderItems, 'purchaseorderitems.xlsx');
  }


  public function getPurchaseOrderDetails($po,$id){
    $sd = $po->prprice()->first()->supplier;
    $getPr = PurchaseRequest::whereHas('prpricing.po', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->pluck('pr_prnum')
      ->toArray();

    $deliveredSub = SupplierInvoice::select(
      Db::raw('SUM(ssi_receivedquantity) as delivered'),
      'ssi_pritem_id')
      ->groupBy('ssi_pritem_id');
    $prNumber = implode(",", $getPr);
    $poItems = $po->poitems()->get()->map(function ($item) {
      return (object)array(
        'id' => $item->id,
        'code' => $item->spoi_code,
        'materialSpecification' => $item->spoi_mspecs,
        'unit' => $item->spoi_uom,
        'unitprice' => $item->spoi_unitprice,
        'quantity' => $item->spoi_quantity,
        'deliveryDate' => $item->spoi_deliverydate,
        'delivered' => $item->invoice()->sum('ssi_receivedquantity')
      ); 
    });
    
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

    return (object) array(
      'poDetails' => $poDetails,
      'poItems' => $poItems,
    );
  }

  public function printPurchaseOrder(Request $request,$id){
    $token = $request->bearerToken();
    $po = PurchaseOrderSupplier::findOrFail($id);
    $user = $po->user;
    $getDetails = $this->getPurchaseOrderDetails($po,$id);
    $poItems = $getDetails->poItems;
    $poDetails = $getDetails->poDetails;
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
      'poItems',
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
    $limit = request()->has('recordCount') ? request()->recordCount : 500;
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
    $limit = request()->has('recordCount') ? request()->recordCount : 500;

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

    if(request()->has('supplier')){
      $q->whereRaw('supplier.id = ?', array(request()->supplier));
    }

    if(request()->has('currency')){
      $q->whereRaw('prprice.prsd_currency = ?', array(request()->currency));
    }

    if(request()->has('month')){
      $q->whereMonth('pr_date', request()->month);
    }

    if(request()->has('year')){
      $q->whereYear('pr_date', request()->year);
    }

    $prList = $q->orderBy('price_id','DESC')
      ->limit($limit)
      ->get();
    $prListLength = count($prList);

    return response()->json([
      'prPriceListLength' => $prListLength,
      'prPriceList' => $prList,
      'supplierList' => $this->getSupplier(),
    ]);
  }

  public function getSupplier(){
    $supplier = Supplier::select('id', 'sd_supplier_name as supplierName')
                  ->orderBy('sd_supplier_name','ASC')
                  ->get();
    return $supplier;
  }

  public function getPrInfo($id)
  {

    $pr = PurchaseRequest::whereHas('jo.poitems.po')->findOrFail($id);
    $jo = $pr->jo;
    $po = $jo->poitems->po;
    $po_id = $po->id;
    $prItems = array();
    $poItems = array();

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

    $poPurchasesHistory = PurchaseOrderSupplier::whereHas('prprice.pr.jo.poitems.po',
      function($q) use ($po_id) {
        $q->where('id', $po_id);
      })
      ->get()
      ->map(function($po) {
        $joNumbers = JobOrder::whereHas('pr.prpricing.po', function($q) use ($po) {
            $q->where('id', $po->id);
          })
          ->get()
          ->pluck('jo_joborder')
          ->implode(',');

        $prNumbers = PurchaseRequest::whereHas('prpricing.po', function($q) use ($po) {
            $q->where('id', $po->id);
          })
          ->get()
          ->pluck('pr_prnum')
          ->implode(',');

        $itemDescription = PurchaseOrderItems::whereHas('jo.pr.prpricing.po', function($q) use ($po) {
            $q->where('id', $po->id);
          })
          ->get()
          ->pluck('poi_itemdescription')
          ->implode(',');

        return array( 
          'supplierName' => $po->prprice()->first()->supplier->sd_supplier_name,
          'joNumber' => $joNumbers,
          'prNumbers' => $prNumbers,
          'poNumber' => $po->spo_ponum,
          'itemDescription' => $itemDescription,
          'totalAmount' => $po->poitems()->sum(Db::raw('spoi_quantity * spoi_unitprice')),
          'isSent' => $po->spo_sentToSupplier ? "YES" : "NO",
        );
        return $po;
      })
      ->values();
    foreach($po->poitems as $row){
      $totalAmt = $row->poi_unitprice * $row->poi_quantity;
      $masterlist = Masterlist::where('m_code',$row->poi_code)->first();

      $budgetPriceTotal = 0;
      $budgetPrice = 'No match record';

      if($masterlist){
        $budgetPrice = $masterlist->m_budgetprice != null ? $masterlist->m_budgetprice : 0;
        $budgetPriceTotal = $budgetPrice * $row->poi_quantity;
      }

      array_push($poItems, array(
        'id' => $row->id,
        'code' => $row->poi_code,
        'itemDescription' => $row->poi_itemdescription,
        'currency' => $row->po->po_currency,
        'totalAmount' => strtoupper($po->po_currency) == 'USD' ? $totalAmt * 50 : $totalAmt,
        'budgetPrice' => $budgetPrice,
        'budgetPriceTotal' => strtoupper($po->po_currency) == 'USD' ? $budgetPriceTotal * 50 : $budgetPriceTotal,
        'isMatchItem' => $jo->poitems->id == $row->id,
      ));
    }

    return response()->json([
      'poPurchasesHistory' => $poPurchasesHistory,
      'poJoDetails' => $poJoDetails,
      'poItems' => $poItems,
      'prItems' => $prItems,
      'supplierList' => $this->getSupplier(),
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
        'currency' => 'required|string|in:PHP,USD',
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
    $this->logCreateAndRemovePriceToPr($pr->pr_prnum,
        $pr->prpricing->supplier->sd_supplier_name, "Added");//log this action
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
    $prpricing = $pr->prpricing;
    $prpricing->fill([
      'prsd_supplier_id' => $request->supplier,
      'prsd_currency' => $request->currency,
    ]);

    if($prpricing->isDirty()){
      $this->logPrSupplierDetailsEdit($pr->pr_prnum,
          $prpricing->getDirty(), $prpricing->getOriginal());
      $prpricing->save();
    }

    foreach($request->prItems as $item){
      $pritem = PurchaseRequestItems::findOrFail($item['id'])->fill([
        'pri_unitprice' => $item['unitPrice'],
        'pri_deliverydate' => $item['dateNeeded'],
      ]);
      if($pritem->isDirty()){
        $this->logPrSupplierDetailsItemEdit($pr->pr_prnum,
          $pritem->getDirty(), $pritem->getOriginal());
        $pritem->save();
      }
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
    $this->logCreateAndRemovePriceToPr($prsd->pr->pr_prnum,
        $prsd->supplier->sd_supplier_name, "Deleted");//log this action
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
        'approver' => $list->pra_approver_user,
        'otherInfo' => $list->pra_otherinfo,
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

  public function addApprovalRequest(Request $request){
    $validator = Validator::make($request->all(),
      array(
        'price_id' => 'required|int',
        'approver' => 'required|string|unique:psms_prapprovaldetails,pra_approver_id,null,id,pra_prs_id,'.$request->price_id,
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

    $pr = $prSupplierDetails->pr;
    $approval->fill([
      'pra_prs_id' => $request->price_id,
      'pra_approver_id' => $request->approver,
      'pra_approver_user' => $user->username,
      'pra_otherinfo' => $pr->jo->jo_joborder." - ". $pr->pr_prnum,
    ]);
    $approval->save();
    $this->logCreateAndRemovalOfApprovalRequest($pr->pr_prnum,
      $request->method, "Added");
    return response()->json([
      'message' => 'Record added',
      'newApprovalRequest' => $this->approvalArray($approval),
      'newPr' => $this->prWithPriceArray($pr),
    ]);
  }

  public function deleteApprovalRequest($id){
    $approval = PurchaseRequestApproval::findOrFail($id);
    $prsd = PurchaseRequestSupplierDetails::findOrFail($approval->pra_prs_id);
    $this->logCreateAndRemovalOfApprovalRequest($prsd->pr->pr_prnum,
      $approval->pra_approvalType, "Deleted");
    $approval->delete();
    $pr = $this->prWithPriceArray($prsd->pr);
    return response()->json([
      'message' => 'Record deleted',
      'newPr' => $pr,
    ]);
  }

  //approval on pr
  public function getPendingPrList(){
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

      if(request()->has('status')) {
        $status = strtoupper(request()->status);
        if($status == "APPROVED")
          $q->whereRaw('pra_approved > 0 and pra_rejected = 0');
        else if($status == "REJECTED")
          $q->whereRaw('pra_rejected > 0 and pra_approved = 0');
        else if($status == "PENDING")
          $q->whereRaw('pra_rejected = 0 and pra_approved = 0');
      }

      if(request()->has('search')) {
        $search = "%".request()->search."%";
        $q->whereRaw('jo_joborder LIKE ?',array($search))
          ->orWhereRaw('pr_prnum LIKE ?',array($search))
          ->orWhereRaw('po_ponum LIKE?',array($search));
      }

      $sort = strtolower(request()->sort);
      if($sort == "asc")
        $q->oldest("psms_prapprovaldetails.created_at");
      else
        $q->latest("psms_prapprovaldetails.created_at");
 

      $isFullLoad = request()->has('start') && request()->has('end');
      if($isFullLoad)
        $list = $q->offset(request()->start)->limit(request()->end)->get();
      else 
        $list = $q->paginate(1000);

    return response()->json([
      'prListLength' => $isFullLoad ? intval(request()->end) : $list->total(),
      'prList' => $isFullLoad ? $list : $list->items(),
    ]);
  }

  public function getPrDetails($id){

    $prsd = PurchaseRequestSupplierDetails::whereHas('pr.jo.poitems.po')->findOrFail($id);
    $pr = $prsd->pr;
    $po = $pr->jo->poitems->po;
    $poitemId = $pr->jo->poitems->id;
    $po_id = $po->id;
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
      $masterlist = Masterlist::where('m_code',$row->poi_code)->first();

      $budgetPriceTotal = 0;
      $budgetPrice = 'No match record';

      if($masterlist){
        $budgetPrice = $masterlist->m_budgetprice != null ? $masterlist->m_budgetprice : 0;
        $budgetPriceTotal = $budgetPrice * $row->poi_quantity;
      }

      array_push($poItems, array(
        'id' => $row->id,
        'code' => $row->poi_code,
        'itemDescription' => $row->poi_itemdescription,
        'quantity' => $row->poi_quantity,
        'currency' => $row->po->po_currency,
        'unitPrice' => $row->poi_unitprice,
        'totalAmount' => strtoupper($po->po_currency) == 'USD' ? $totalAmt * 50 : $totalAmt,
        'budgetPrice' => $budgetPrice,
        'budgetPriceTotal' => strtoupper($po->po_currency) == 'USD' ? $budgetPriceTotal * 50 : $budgetPriceTotal,
        'isMatchItem' => $poitemId == $row->id,
      ));
    }
    $poPurchasesHistory = PurchaseOrderSupplier::whereHas('prprice.pr.jo.poitems.po',
        function($q) use ($po_id) {
          $q->where('id', $po_id);
        })
        ->get()
        ->map(function($po) {
          $joNumbers = JobOrder::whereHas('pr.prpricing.po', function($q) use ($po) {
              $q->where('id', $po->id);
            })
            ->get()
            ->pluck('jo_joborder')
            ->implode(',');

          $prNumbers = PurchaseRequest::whereHas('prpricing.po', function($q) use ($po) {
              $q->where('id', $po->id);
            })
            ->get()
            ->pluck('pr_prnum')
            ->implode(',');

          $itemDescription = PurchaseOrderItems::whereHas('jo.pr.prpricing.po', function($q) use ($po) {
              $q->where('id', $po->id);
            })
            ->get()
            ->pluck('poi_itemdescription')
            ->implode(',');

          return array( 
            'supplierName' => $po->prprice()->first()->supplier->sd_supplier_name,
            'joNumber' => $joNumbers,
            'prNumbers' => $prNumbers,
            'poNumber' => $po->spo_ponum,
            'itemDescription' => $itemDescription,
            'totalAmount' => $po->poitems()->sum(Db::raw('spoi_quantity * spoi_unitprice')),
            'isSent' => $po->spo_sentToSupplier ? "YES" : "NO",
          );
          return $po;
        })
        ->values();

    return response()->json([
      'prItems' => $prItems,
      'poDetails' => $poDetails,
      'poItems' => $poItems,
      'poHistory' => $poPurchasesHistory,
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
      return response()-json(['errors' =>
        ['You can only create purchase order with same supplier & currency'] ], 422);

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

  public function purchaseOrderInfo($id)
  {
    $po = PurchaseOrderSupplier::findOrFail($id);
    $poItems = PurchaseRequestItems::whereHas('pr.prpricing.po', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->get()
      ->map(function($item) {
        return array(
          'id' => $item->id,
          'prnumber' => $item->pr->pr_prnum,
          'code' => $item->pri_code,
          'materialSpecification' => $item->pri_mspecs,
          'unit' => $item->pri_uom,
          'unitprice' => $item->pri_unitprice,
          'quantity' => $item->pri_quantity,
          'deliveryDate' => $item->pri_deliverydate
        );
      })
      ->toArray();

    return response()->json([
      'poItems' => $poItems,
    ]);
  }

  public function getPurchaseOrder()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 500;

    $joinQry = "SELECT
      prms_prlist.id,
      prsd.prsd_supplier_id as supplier_id,
      prsd.prsd_spo_id,
      prsd.prsd_currency as currency,
      GROUP_CONCAT(pr_prnum) as prnumbers
      -- CAST(sum(itemCount) as int) as itemCount,
      -- CAST(sum(totalPrQuantity) as int) as totalPoQuantity,
      -- IFNULL(CAST(sum(quantityDelivered) as int),0) as quantityDelivered
      FROM prms_prlist
      LEFT JOIN psms_prsupplierdetails prsd 
      ON prsd.prsd_pr_id = prms_prlist.id
      -- LEFT JOIN (SELECT count(*) as itemCount,
      -- SUM(pri_quantity) as totalPrQuantity,pri_pr_id,id
      -- FROM prms_pritems
      -- GROUP BY pri_pr_id) pri
      -- ON pri.pri_pr_id = prms_prlist.id
      -- LEFT JOIN (SELECT SUM(ssi_receivedquantity + ssi_underrunquantity) as quantityDelivered,ssi_pritem_id 
      -- FROM psms_supplierinvoice
      -- WHERE ssi_receivedquantity > 0
      -- GROUP BY ssi_pritem_id) prsi
      -- ON prsi.ssi_pritem_id = pri.id
      GROUP BY prsd_spo_id";

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');

    $itemsTbl = DB::table('prms_pritems')
      ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
      ->groupBy('pri_pr_id');

    $supplierInvoice = DB::table('psms_supplierinvoice')
      ->select(DB::raw('SUM(ssi_receivedquantity + ssi_underrunquantity) as quantityDelivered'),
        'ssi_poitem_id'
      )
      ->groupBy('ssi_poitem_id');

    $spoItems = DB::table('psms_spurchaseorderitems')
      ->leftJoinSub($supplierInvoice, 'invoice', function($join){
        $join->on('invoice.ssi_poitem_id','=','psms_spurchaseorderitems.id');
      })
      ->select(DB::raw('count(*) as itemCount'),
        'spoi_po_id',
        DB::raw('IFNULL(CAST(sum(quantityDelivered) as int),0) as quantityDelivered'),
        DB::raw('CAST(SUM(spoi_quantity) as int) as totalPoQuantity')
      )
      ->groupBy('spoi_po_id');

    

    $q = PurchaseOrderSupplier::has('prprice.pr.jo.poitems.po')
      ->leftJoinSub($joinQry,'pr', function($join){
        $join->on('pr.prsd_spo_id','=','psms_spurchaseorder.id');
      })
      ->leftJoinSub($spoItems, 'spoi', function($join){
        $join->on('spoi.spoi_po_id','=','psms_spurchaseorder.id');
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
        'quantityDelivered',
        'totalPoQuantity',
        Db::raw('IF(spo_sentToSupplier = 0 && quantityDelivered < 1,
          "PENDING",
          IF(quantityDelivered > 0,
            IF(quantityDelivered >= totalPoQuantity,
              "DELIVERED",
              "PARTIAL"
            ),
            "OPEN"
          )
        ) as status'),
        'spo_date as date'
      );

    if(request()->has('poStatus')){
      $status = strtolower(request()->poStatus);  
      if($status == 'pending')
        $q->whereRaw('spo_sentToSupplier < 1 and quantityDelivered = 0');
      else if($status == 'open')
        $q->whereRaw('spo_sentToSupplier = 1 and quantityDelivered < 1');
      else if($status == 'delivered')
        $q->whereRaw('quantityDelivered >= totalPoQuantity');
      else if($status == 'partial')
        $q->whereRaw('quantityDelivered > 0 and quantityDelivered < totalPoQuantity');
    }

    if(request()->has('supplier')){
      $q->whereRaw('supplier.id = ?', array(request()->supplier));
    }

    if(request()->has('currency')){
      $q->whereRaw('currency = ?', array(request()->currency));
    }

    if(request()->has('month')){
      $q->whereMonth('spo_date', request()->month);
    }

    if(request()->has('year')){
      $q->whereYear('spo_date', request()->year);
    }

    $poList = $q->orderBy('id','DESC')
    ->limit($limit)
    ->get();

    return response()->json([
      'poList' => $poList,
      'supplierList' => $this->getSupplier(),
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
    $addedPRs = array();

    //update prsd add po id
    foreach($request->prsID as $id)
    {
      $prs = PurchaseRequestSupplierDetails::findOrFail($id);
      if(!$prs->po){
        $prs->update([
          'prsd_spo_id' => $purchaseOrder->id,
        ]);
        array_push($addedIds, $id);
        array_push($addedPRs, $prs->pr->pr_prnum);
      }
    }

    //copy items from pr to poitem table
    $getPurchaseRequestItems = PurchaseRequestItems::whereHas('pr.prpricing', function($q) use ($addedIds){
        return $q->whereIn('id', $addedIds);
      })
      ->select(
        DB::raw('IF(count(*) > 0,group_concat(pri_code),pri_code) as code'),
        'pri_mspecs as materialSpecification',
        'pri_uom as unit',
        'pri_unitprice as unitprice',
        DB::raw('sum(pri_quantity) as quantity'),
        'pri_deliverydate as deliveryDate',
        'pri_remarks as remarks'
      )
      ->orderBy('pri_deliverydate','desc')
      ->groupBy('pri_mspecs', 'pri_uom', 'pri_unitprice')
      ->get();

    foreach($getPurchaseRequestItems as $row){
      $newCode = implode(",",array_unique(explode(",", $row->code)));
      $purchaseOrder->poitems()->create([
        'spoi_code' => $newCode,
        'spoi_mspecs' => $row->materialSpecification,
        'spoi_uom' => $row->unit,
        'spoi_quantity' => $row->quantity,
        'spoi_unitprice' => $row->unitprice,
        'spoi_remarks' => $row->remarks,
        'spoi_deliverydate' => $row->deliveryDate,
      ]);
    }
    //end copy items

    $this->logCreateAndRemovalOfPotoPr(implode(",",$addedPRs),$request->poSeries,"Added");

    return response()->json([
      'addedIDs' => $addedIds,
      'message' => 'Record added',
    ]);
  }

  public function markAsSentToSupplier($id){
    $po = PurchaseOrderSupplier::findOrFail($id);

    if($po->spo_sentToSupplier)
      return response()->json(['errors' =>
          ['Purchase order already mark as OPEN'] ],422);

    $po->update([
      'spo_sentToSupplier' => 1,
    ]);

    $prNumbers = PurchaseRequest::whereHas('prpricing.po', function($q)
      use ($id){
        return $q->where('id', $id);
      })
      ->pluck('pr_prnum')
      ->toArray();
    $prNumber = implode(",", $prNumbers);
    $item = $po->poitems()->get();

    $itemCount = $item->count();
    $totalPoQuantity = $item->sum('spoi_quantity');
    $quantityDelivered = SupplierInvoice::whereHas('poitem.spo', function($q) 
      use ($id){
        return $q->where('id', $id);
      })
      ->sum('ssi_receivedquantity');
    $status = 'OPEN';
    if($po->spo_sentToSupplier != 0 || $quantityDelivered > 0){
      if($quantityDelivered > 0)
        $status = "PARTIAL";
      else if($quantityDelivered >= $totalPoQuantity)
        $status = "DELIVERED";
    }else 
      $status = "PENDING";

    $this->logSentPOToSupplier($po->po_ponum, $status);
      
    $newPo = array(
      'id' => $po->id,
      'supplier' => $po->prprice()->first()->supplier->sd_supplier_name,
      'poNum' => $po->spo_ponum,
      'prNumbers' => $prNumber,
      'currency' => $po->prprice()->first()->prsd_currency,
      'itemCount' => $itemCount,
      'quantityDelivered' => $quantityDelivered,
      'totalPoQuantity' => $totalPoQuantity,
      'status' => $status,
      'date' => $po->spo_date
    );
    return response()->json([
      'message' => 'Record updated',
      'newPo' => $newPo,
    ]);
  }

  public function cancelPurchaseOrder($id)
  {
    $po = PurchaseOrderSupplier::findOrFail($id);
    $hasDelivery = PurchaseOrderSupplierItems::has('invoice')
      ->count();
    if($hasDelivery > 0)
      return response()->json(['errors' =>
          ['Cannot cancel purchase order with delivery'] ],422);

    $prs = PurchaseRequest::whereHas('prpricing.po', function($q) use ($id){
      $q->where('id', $id);
    })->get()
      ->pluck('pr_prnum')
      ->toArray();
    $this->logCreateAndRemovalOfPotoPr(implode(",",$prs), $po->spo_ponum, "Deleted");

    $po->prprice()->update([
      'prsd_spo_id' => 0,
    ]);
    $po->poitems()->delete();
    $po->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  public function getPoItems()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 500;

    $prPrice = Db::table('psms_prsupplierdetails')
      ->select(
        'prsd_spo_id',
        'prsd_supplier_id',
        'prsd_currency as currency'
      )
      ->groupBy('prsd_spo_id');

    $supplier = Db::table('psms_supplierdetails')
      ->select(
        'id',
        'sd_supplier_name as supplier'
      );

    $po = Db::table('psms_spurchaseorder')
      ->select(
        'id',
        'spo_ponum as poNum'
      );

    $invoice = Db::table('psms_supplierinvoice')
      ->select(
        'ssi_poitem_id',
        DB::raw('count(*) as invoiceCount'),
        DB::raw('CAST(SUM(ssi_receivedquantity + ssi_underrunquantity) as int) as totalDelivered'),
        DB::raw('CAST(SUM(ssi_drquantity) as int) as totalInvoiceQty')
      )
      ->groupBy('ssi_poitem_id');

    $q = PurchaseOrderSupplierItems::has('spo')
      ->leftJoinSub($invoice, 'invoice', function($join){
        $join->on('invoice.ssi_poitem_id','=','psms_spurchaseorderitems.id');
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('po.id','=','psms_spurchaseorderitems.spoi_po_id');
      })
      ->leftJoinSub($prPrice, 'prprice', function($join){
        $join->on('po.id','=','prprice.prsd_spo_id');
      })
      ->leftJoinSub($supplier, 'supplier', function($join){
        $join->on('prprice.prsd_supplier_id','=','supplier.id');
      })
      ->select(
        'psms_spurchaseorderitems.id',
        DB::raw('
          IF(IFNULL(totalDelivered,0) >= spoi_quantity,
            "DELIVERED",
            "OPEN") as status
        '),
        'poNum',
        'supplier',
         DB::raw('spoi_code as code'),
        'spoi_mspecs as materialSpecification',
        'spoi_unitprice as unitPrice',
        'spoi_quantity as quantity',
        'currency',
        DB::raw('CAST((spoi_unitprice * spoi_quantity) as int) as totalAmount'),
        DB::raw('CAST(IFNULL(totalDelivered,0) as int) as totalDelivered'),
        DB::raw('CAST(IFNULL(invoiceCount,0) as int) as invoiceCount'),
        DB::raw('CAST(IFNULL(totalInvoiceQty,0) as int) as totalInvoiceQty'),
        Db::raw('CAST((spoi_quantity - IFNULL(totalDelivered,0)) as int) as remaining'),
        'spoi_deliverydate as deliverydate'
      );

      if(request()->has('poItemStatus')){
        $status = strtolower(request()->poItemStatus);  
        if($status == 'open')
          $q->whereRaw('sum(spoi_quantity) > sum(IFNULL(totalDelivered,0))');
        else if($status == 'delivered')
          $q->whereRaw('sum(IFNULL(totalDelivered,0) >= sum(spoi_quantity)');
      }

      if(request()->has('supplier')){
        $q->whereRaw('supplier.id = ?', array(request()->supplier));
      }

      if(request()->has('currency')){
        $q->whereRaw('currency = ?', array(request()->currency));
      }

      if(request()->has('month')){
        $q->whereMonth('spoi_deliverydate', request()->month);
      }

      if(request()->has('year')){
        $q->whereYear('spoi_deliverydate', request()->year);
      }

      $poItems = $q->latest('id')
      ->limit($limit)
      ->get();

      return response()->json([
        'poItems' => $poItems,
        'supplierList' => $this->getSupplier(),
      ]);
  }

  public function purchaseOrderItemGetArray($item){
    $totalDelivered = $item->invoice()
      ->sum(Db::raw('ssi_receivedquantity + ssi_underrunquantity'));
    $totalInvoiceQty = $item->invoice()->sum(Db::raw('ssi_drquantity'));
    $status = "OPEN";

    if($totalDelivered >= $item->spoi_quantity)
      $status = "DELIVERED";

    return (object) array(
      'id' => $item->id,
      'status' => $status,
      'poNum' => $item->spo->spo_ponum,
      'supplier' => $item->spo->prprice()->first()->supplier->sd_supplier_name,
      'code' => $item->spoi_code,
      'materialSpecification' => $item->spoi_mspecs,
      'unitPrice' => $item->spoi_unitprice,
      'quantity' => $item->spoi_quantity,
      'currency' => $item->spo->prprice()->first()->prsd_currency,
      'totalAmount' => $item->spoi_unitprice * $item->spoi_quantity,
      'totalDelivered' => $totalDelivered,
      'invoiceCount' => $item->invoice()->count(),
      'totalInvoiceQty' => $totalInvoiceQty,
      'remaining' => $item->spoi_quantity - $totalDelivered,
      'deliverydate' => $item->spoi_deliverydate,
    );
  }

  public function purchaseOrderDeliveryGetArray($invoice){
    return (object) array(
      'id' => $invoice->id,
      'invoice' => $invoice->ssi_invoice,
      'dr' => $invoice->ssi_dr,
      'date' => $invoice->ssi_date,
      'quantity' => $invoice->ssi_drquantity ?? 0,
      'rrNumber' => $invoice->ssi_rrnum,
      'inspectedQty' => $invoice->ssi_inspectedquantity ?? 0,
      'receivedQty' => $invoice->ssi_receivedquantity ?? 0,
      'underrun' => $invoice->ssi_underrunquantity ?? 0,
      'remarks' => $invoice->ssi_remarks,
    );
  }

  public function purchaseOrderDeliveryInputArray($invoice){
    return array(
      'ssi_invoice' => $invoice->invoice,
      'ssi_dr' => $invoice->dr,
      'ssi_date' => $invoice->date,
      'ssi_drquantity' => $invoice->quantity ?? 0,
      'ssi_underrunquantity' => $invoice->underrun ?? 0
    );
  }

  public function getPurchaseOrderDeliveries($id){
    $supplierDeliveries = SupplierInvoice::whereHas('poitem', function($q) use ($id){
        $q->where('ssi_poitem_id', $id);
      })
      ->get()
      ->map(function($invoice){
        return $this->purchaseOrderDeliveryGetArray($invoice);
      })
      ->toArray();

    return response()->json([
      'supplierDeliveries' => $supplierDeliveries
    ]); 
  }

  public function purchaseOrderValidationArray($totalRemaining){
    return array(
      'totalQty' => 'integer|min:1|required|max:'.$totalRemaining,
      'quantity' => 'integer|nullable|required_if:underrun,null,0',
      'underrun' => 'integer|nullable|required_if:quantity,null,0',
      'date' => 'required|before_or_equal:'.date('Y-m-d'),
      'dr' => 'string|max:70|required|regex:/^[a-zA-Z0-9_ -]*$/',
      'invoice' => 'string|max:70|required|regex:/^[a-zA-Z0-9_ -]*$/'
    );
  }

  public function addDeliveryToPO(Request $request){

    $poItem = PurchaseOrderSupplierItems::findOrFail($request->item_id);
    $totalRemaining = intval($poItem->spoi_quantity)
      - intval($poItem->invoice()->sum(Db::raw('ssi_receivedquantity + ssi_underrunquantity')) )
      - intval($poItem->invoice()->sum(Db::raw('ssi_drquantity')) );

    $totalQty = array('totalQty' => intval($request->quantity) + intval($request->underrun));
    $validator = Validator::make(array_merge($request->all(),$totalQty),
      $this->purchaseOrderValidationArray($totalRemaining),
      [],
      [
        'dr' => 'delivery receipt',
        'invoice' => 'invoice number',
        'totalQty' => 'Total sum of quantity and underrun',
      ]);

    $validator->sometimes('dr', 'unique:psms_supplierinvoice,ssi_dr,
      null,id,ssi_poitem_id,'.$request->item_id, function($input) {
        return $input->dr != 'NA';
      })->sometimes('invoice', 'unique:psms_supplierinvoice,ssi_invoice,
      null,id,ssi_poitem_id,'.$request->item_id, function($input) {
        return $input->invoice != 'NA';
      });

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()], 422);

    $newDelivery = $poItem->invoice()
      ->create($this->purchaseOrderDeliveryInputArray($request));

    $this->logAddRemovedDeliveredToPo(
      $poItem->spo->spo_ponum ?? 'NO PO',
      $poItem->spoi_mspecs,
      $request->invoice,
      $request->dr,
      $request->quantity,
      "Added"
    );
    $poItem->refresh();
    $newItem = $this->purchaseOrderItemGetArray($poItem);
    $newDelivery = $this->purchaseOrderDeliveryGetArray($newDelivery);

    return response()->json([
      'newItem' => $newItem,
      'newDelivery' => $newDelivery,
      'message' => 'Record added',
    ]);
  }

  public function updateDeliveryToPo(Request $request, $id){
    $poItem = PurchaseOrderSupplierItems::findOrFail($request->item_id);
    $invoice = SupplierInvoice::findOrFail($id);
    $totalRemaining = intval($poItem->spoi_quantity)
      - intval($poItem->invoice()
          ->sum(Db::raw('ssi_receivedquantity + ssi_underrunquantity')) )
      - intval($poItem->invoice()
          ->sum(Db::raw('ssi_drquantity')) ) + $invoice->ssi_drquantity;
    $totalQty = array('totalQty' => intval($request->quantity) + intval($request->underrun));
    $validator = Validator::make(array_merge($request->all(),$totalQty),
      $this->purchaseOrderValidationArray($totalRemaining),
      [],
      [
        'dr' => 'delivery receipt',
        'invoice' => 'invoice number',
        'totalQty' => 'Total sum of quantity and underrun',
      ]);

    $validator->sometimes('dr', 'unique:psms_supplierinvoice,ssi_dr,
      '.$id.',id,ssi_poitem_id,'.$request->item_id, function($input) {
        return $input->dr != 'NA';
      })->sometimes('invoice', 'unique:psms_supplierinvoice,ssi_invoice,
      '.$id.',id,ssi_poitem_id,'.$request->item_id, function($input) {
        return $input->invoice != 'NA';
      });

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()], 422);

    $invoice->fill($this->purchaseOrderDeliveryInputArray($request));
    if($invoice->isDirty()){
      $this->logEditedDeliveredToPo(
        $poItem->spo->spo_ponum ?? 'NO PO',
        $poItem->spoi_mspecs,
        $invoice->getDirty(),
        $invoice->getOriginal());
      $invoice->save();
    }
    $poItem->refresh();
    $newItem = $this->purchaseOrderItemGetArray($poItem);
    $newDelivery = $this->purchaseOrderDeliveryGetArray($invoice);

    return response()->json([
      'newItem' => $newItem,
      'newDelivery' => $newDelivery,
      'message' => 'Record updated',
    ]);
  }

  public function deleteDeliveryPo($id){
    $invoice = SupplierInvoice::findOrFail($id);
    $poItem = $invoice->poitem;
    $this->logAddRemovedDeliveredToPo(
      $poItem->spo->spo_ponum ?? 'NO PO',
      $poItem->spoi_mspecs,
      $invoice->ssi_invoice,
      $invoice->ssi_dr,
      $invoice->ssi_drquantity,
      "Deleted"
    );
    $invoice->delete();

    $newItem = $this->purchaseOrderItemGetArray($poItem);
    return response()->json([
      'newItem' => $newItem,
      'message' => 'Record deleted',
    ]);
  }

  public function getAllSupplier(){
    $supplierList = Supplier::all()
      ->map(function($supplier) {
        return array(
          'id' => $supplier->id,
          'name' => $supplier->sd_supplier_name,
          'address' => $supplier->sd_address,
          'tin' => $supplier->sd_tin,
          'attention' => $supplier->sd_attention,
          'paymentTerms' => $supplier->sd_paymentterms,
        );
      })
      ->toArray();

    return response()->json([
      'supplierList' => $supplierList,
    ]);
  }

  public function addSupplier(Request $request){

    $validator = Validator::make($request->all(), [
      'name' => 'string|max:250|regex:/^[a-zA-Z0-9_ -]*$/|unique:psms_supplierdetails,sd_supplier_name',
      'address' => 'string|max:255|nullable',
      'tin' => 'string|max:50|nullable',
      'attention' => 'string|max:50|nullable',
      'paymentTerms' => 'integer|min:0|nullable',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $supplier = new Supplier;
    $supplier->fill([
      'sd_supplier_name' => ucwords($request->name),
      'sd_address' => $request->address,
      'sd_tin' => $request->tin,
      'sd_attention' => $request->attention,
      'sd_paymentterms' => $request->paymentTerms,
    ])->save();
    $this->logAddRemovedSupplier($request->name, "Added");
    $newSupplier = array(
      'id' => $supplier->id,
      'name' => $supplier->sd_supplier_name,
      'address' => $supplier->sd_address,
      'tin' => $supplier->sd_tin,
      'attention' => $supplier->sd_attention,
      'paymentTerms' => $supplier->sd_paymentterms,
    );

    return response()->json([
      'newSupplier' => $newSupplier,
      'message' => 'Record added',
    ]);

  }

  public function updateSupplier(Request $request,$id){

    $validator = Validator::make($request->all(), [
      'name' => 'string|max:250|regex:/^[a-zA-Z0-9_ -]*$/|unique:psms_supplierdetails,sd_supplier_name,'.$id,
      'address' => 'string|max:255|nullable',
      'tin' => 'string|max:50|nullable',
      'attention' => 'string|max:50|nullable',
      'paymentTerms' => 'integer|min:0|nullable',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $supplier = Supplier::findOrFail($id);
    $name = $supplier->sd_supplier_name;
    $supplier->fill([
      'sd_supplier_name' => ucwords($request->name),
      'sd_address' => $request->address,
      'sd_tin' => $request->tin,
      'sd_attention' => $request->attention,
      'sd_paymentterms' => $request->paymentTerms,
    ]);

    if($supplier->isDirty()){
      $this->logEditedSupplier($name, $supplier->getDirty(), $supplier->getOriginal());
      $supplier->save();
    }

    $newSupplier = array(
      'id' => $supplier->id,
      'name' => $supplier->sd_supplier_name,
      'address' => $supplier->sd_address,
      'tin' => $supplier->sd_tin,
      'attention' => $supplier->sd_attention,
      'paymentTerms' => $supplier->sd_paymentterms,
    );

    return response()->json([
      'newSupplier' => $newSupplier,
      'message' => 'Record updated',
    ]);
  }

  public function deleteSupplier($id){
    $supplier = Supplier::findOrFail($id);
    $this->logAddRemovedSupplier($supplier->sd_supplier_name, "Deleted");
    $supplier->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  public function getSupplierTotal($id, $date){
    $date1 = Carbon::parse($date[0]);
    $date2 = Carbon::parse($date[1]);
    return PurchaseOrderSupplierItems::whereHas('spo.prprice.supplier', function($q) use ($id){
      $q->where('id', $id);
    })
    ->get()
    ->map(function($item) use ($date1, $date2) {
      $totalPhp = 0;
      $totalUsd = 0;
      $currency =  $item->spo->prprice()->first()->prsd_currency;
      $totalReceived = $item->invoice()
        ->whereBetween('ssi_date', [$date1, $date2])
        ->sum('ssi_receivedquantity');

      if(strtoupper($currency) == 'PHP')
        $totalPhp+= $item->spoi_unitprice * $totalReceived;
      else
        $totalUsd+= ($item->spoi_unitprice * $totalReceived) * 47;

      return array(
        'totalPhp' => $totalPhp,
        'totalUsd' => $totalUsd
      );
    })
    ->toArray();
  }

  public function getPurchasesReport(Request $request) {

    $validator = Validator::make($request->all(), [
      'date' => 'array|required|min:2|max:2',
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()]);
    }

    $invoice = DB::table('psms_supplierinvoice')->select(
      DB::raw('sum(ssi_receivedquantity) as totalReceivedQty'),
      'ssi_poitem_id'
    )->groupBy('ssi_poitem_id');

    $items = PurchaseOrderSupplierItems::whereHas('invoice', function($q){
        $q->where('ssi_rrnum','!=', null);
      })
      ->leftJoinSub($invoice, 'invoice', function($join){
        $join->on('psms_spurchaseorderitems.id', '=' ,'invoice.ssi_poitem_id');
      })
      ->select(
        'spoi_mspecs as materialSpecification',
        Db::raw('cast(sum(totalReceivedQty) as int) as totalQtyBought')
      )
      ->orderBy('totalQtyBought', 'DESC')
      ->groupBy('spoi_mspecs')
      ->get();

    $date = $request->date;
    $supplierTotal = Supplier::all()
      ->map(function($supplier) use($date) {

        $total = $this->getSupplierTotal($supplier->id, $date);
        return (object) array(
          'supplierName' => $supplier->sd_supplier_name,
          'totalPhp' => array_sum(array_column($total, 'totalPhp')),
          'totalUsd' => array_sum(array_column($total, 'totalUsd')),
        );
      })
      ->sortByDesc('totalPhp')
      ->values();
    return response()->json([
      'items' => $items,
      'supplierTotal' => $supplierTotal,
    ]);

  }
}