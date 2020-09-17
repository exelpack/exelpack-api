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
          ->orWhereRaw('po_ponum LIKE?',array($search));
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
}
