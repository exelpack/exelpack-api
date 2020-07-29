<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PurchaseRequestApproval;
use App\PurchaseRequestSupplierDetails;
use App\Masterlist;
use App\PurchaseOrderSupplier;
use App\JobOrder;
use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\Customers;
use App\PurchaseRequest;
use DB;
class OperationController extends Controller
{
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
          $budgetPrice - $item->pri_unitprice) / ($budgetPrice > 0 ? $budgetPrice : 1)) * 100),2,'.','')."%)" ,
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

  public function requestionAction(Request $data, $id)
  {
    $request = PurchaseRequestApproval::findOrFail($id);

    if(Auth()->user()->id !== $request->pra_approver_id)
      return response()->json(['errors' => ['Permission denied']], 422);

    if($request->pra_approved > 0 || $request->pra_rejected > 0)
      return response()->json(['errors' => ['Request already approved or rejected'] ], 422); 

    if(strtolower($data->type) != 'approved' && strtolower($data->type) != 'rejected')
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

}
