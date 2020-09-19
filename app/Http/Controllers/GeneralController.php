<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PurchaseRequestApproval;
use App\PurchaseOrderApproval;
use App\PurchaseRequestSupplierDetails;
use App\Masterlist;
use App\PurchaseOrderSupplier;
use App\JobOrder;
use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\Customers;
use App\PurchaseRequest;
use DB;
class GeneralController extends Controller
{
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
      ->select('id','prsd_supplier_id','prsd_pr_id', 'prsd_currency', 'prsd_spo_id')
      ->groupBy('id');

    $spo = DB::table('psms_spurchaseorder')
     	->select('id', 'spo_ponum', 'spo_date')
     	->groupBy('id');

    $q = PurchaseOrderApproval::where('poa_approver_id',$userId)
    	->leftJoinSub($spo, 'spo', function($join){
    		$join->on('spo.id', '=', 'psms_poapprovaldetails.poa_po_id');
    	})
      ->leftJoinSub($prPrice, 'prprice', function($join){
        $join->on('spo.id','=','prprice.prsd_spo_id');
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
        DB::raw('IF(psms_poapprovaldetails.poa_approved > 0,"APPROVED",
        IF(psms_poapprovaldetails.poa_rejected > 0,"REJECTED", "PENDING")) as status'),
        'psms_poapprovaldetails.id',
        'spo.id as po_id',
        'supplier.sd_supplier_name as supplier',
        'spo_ponum as spoNum',
        'jo_joborder as joNum',
        'pr_prnum as prNum',
        'po_ponum as poNum',
        'prsd_currency as currency',
        'psms_poapprovaldetails.created_at as created_at',
        'psms_poapprovaldetails.poa_remarks as remarks',
      ]);

      if(request()->has('status')) {
        $status = strtoupper(request()->status);
        if($status == "APPROVED")
          $q->whereRaw('poa_approved > 0 and poa_rejected = 0');
        else if($status == "REJECTED")
          $q->whereRaw('poa_rejected > 0 and poa_approved = 0');
        else if($status == "PENDING")
          $q->whereRaw('poa_rejected = 0 and poa_approved = 0');
      }

      if(request()->has('search')) {
        $search = "%".request()->search."%";
        $q->whereRaw('jo_joborder LIKE ?',array($search))
          ->orWhereRaw('pr_prnum LIKE ?',array($search))
          ->orWhereRaw('po_ponum LIKE?',array($search))
          ->orWhereRaw('spo_ponum LIKE?',array($search));
      }

      $sort = strtolower(request()->sort);
      if($sort == "asc")
        $q->oldest("psms_poapprovaldetails.created_at");
      else
        $q->latest("psms_poapprovaldetails.created_at");
 

      $isFullLoad = request()->has('start') && request()->has('end');
      if($isFullLoad)
        $list = $q->offset(request()->start)->limit(request()->end)->get();
      else 
        $list = $q->paginate(1000);

    return response()->json([
      'poListLength' => $isFullLoad ? intval(request()->end) : $list->total(),
      'poList' => $isFullLoad ? $list : $list->items(),
    ]);
  }


  public function getPrDetails($id){
    $spo = PurchaseOrderSupplier::whereHas('prprice.pr.jo.poitems.po')->findOrFail($id);
    $po = $spo->prprice()->first()->pr->jo->poitems->po;
    $poitemId = $spo->prprice()->first()->pr->jo->poitems->id;
    $po_id = $po->id;
    $spoItems = array();
    $poItems = array();
    $poDetails = array(
      'poNumber' => $po->po_ponum,
      'customerName' => $po->customer->companyname,
      'poDate' => $po->po_date,
    );

    foreach($spo->poitems as $item){
      $masterlist = Masterlist::where('m_code',$item->spoi_code)->first();
      $costing = 'No match record';
      $budgetPrice = 'No match record';

      if($masterlist){
        $budgetPrice = $masterlist->m_budgetprice != null ? $masterlist->m_budgetprice : 0;
      }

      $bpPercent = "";
      if($budgetPrice > 0) {
				$bpPercent = "(".number_format(((($budgetPrice - $item->spoi_unitprice) / $budgetPrice) * 100),2,'.','')."%)";
      }

       array_push($spoItems, array(
        'id' => $item->id,
        'code' => $item->spoi_code,
        'mspecs' => $item->spoi_mspecs,
        'unit' => $item->spoi_uom,
        'quantity' => $item->spoi_quantity,
        'unitPrice' => $item->spoi_unitprice,
        'currency' => $spo->prprice()->first()->prsd_currency,
        'amount' => $item->spoi_quantity * $item->spoi_unitprice,
        'dateNeeded' => $item->spoi_deliverydate,
        'budgetPrice' => $budgetPrice.$bpPercent
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
    		->where('id', '!=', $id)
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
      'spoItems' => $spoItems,
      'poDetails' => $poDetails,
      'poItems' => $poItems,
      'poHistory' => $poPurchasesHistory,
    ]);
  }

  private function getRequestInformation($request) {
  	$status = "PENDING";
    if($request->poa_approved > 0)
      $status = "APPROVED";

    if($request->poa_rejected > 0)
        $status = "REJECTED";

    $prprice = $request->po->prprice()->first();
    $updatedRequest = array(
      'status' => $status,
      'id' => $request->id,
      'po_id' => $request->po->id,
      'supplier' => $prprice->supplier->sd_supplier_name,
      'joNum' => $prprice->pr->jo->jo_joborder,
      'prNum' => $prprice->pr->pr_prnum,
      'poNum' => $prprice->pr->jo->poitems->po->po_ponum,
      'created_at' => $request->created_at,
      'remarks' => $request->poa_remarks,
    );

    return $updatedRequest;
  }


  public function requestionAction(Request $data, $id)
  {
    $request = PurchaseOrderApproval::findOrFail($id);

    if(Auth()->user()->id !== $request->poa_approver_id)
      return response()->json(['errors' => ['Permission denied']], 422);

    if($request->poa_approved > 0 || $request->poa_rejected > 0)
      return response()->json(['errors' => ['Request already approved or rejected'] ], 422); 

    if(strtolower($data->type) != 'approved' && strtolower($data->type) != 'rejected')
      return response()->json(['errors' => ['Type not valid']], 422); 

    $request->fill([
      'poa_approved' => strtolower($data->type) == 'approved' ? 1 : 0,
      'poa_rejected' => strtolower($data->type) == 'rejected' ? 1 : 0,
      'poa_date' => date('Y-m-d'),
    ]);
    $request->save();
    $request->refresh();
    
    
    return response()->json([
      'updatedRequest' => $this->getRequestInformation($request),
      'message' => 'Record '.strtoupper($data->type)
    ]);
  }

  public function addRemarks(Request $data, $id)
  {
    $request = PurchaseOrderApproval::findOrFail($id);
    $request->update([
      'poa_remarks' => $data->remarks,
    ]);
    $request->refresh();
    return response()->json([
      'updatedRequest' => $this->getRequestInformation($request),
      'message' => 'Record updated',
    ]);
  }
}
