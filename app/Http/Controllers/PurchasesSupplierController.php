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

use App\PurchaseRequest;
use App\PurchaseRequestItems;
use App\Masterlist;
use App\Supplier;

class PurchasesSupplierController extends Controller
{

  public function getPrList()
  {
    $limit = request()->has('recordCount') ? request()->recordCount : 1000;
    $pageSize = request()->pageSize;
    $prPrice = DB::table('psms_prsupplierdetails')
      ->select('prsd_pr_id',DB::raw('count(*) as hasSupplier'),
      'prsd_supplier_id','prsd_sentForApproval','prsd_approvalType')
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

    $q = DB::table('prms_prlist')
          ->leftJoinSub($prPrice, 'prprice', function($join){
            $join->on('prms_prlist.id','=','prprice.prsd_pr_id');
          })
          ->leftJoinSub($supplier, 'supplier', function($join){
            $join->on('prprice.prsd_supplier_id','=','supplier.id');
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
      DB::raw('IF(prprice.hasSupplier > 0,"W/ PRICE","NO PRICE") as status'),
      'pr_prnum as prNum',
      'jo.jo_joborder as joNum',
      'po.po_ponum as poNum',
      'prms_prlist.pr_date as date',
      'supplier.sd_supplier_name as supplierName',
      'supplier.id as supplierId',
      'prms_prlist.pr_remarks as remarks',
      'items.itemCount'
    ]);
    $prList = $q->limit($limit)->get();
    $prListLength = count($prList);

    return response()->json([
      'prListLength' => $prListLength,
      'prList' => $prList,
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
      'item_desc' => $jo->poitems->poi_itemdescription,
      'part_num' => $jo->poitems->poi_partnum,
      'po_qty' => $jo->poitems->poi_quantity,
      'unitprice' => $jo->poitems->poi_unitprice,
      'currency' => $jo->poitems->po->po_currency,
      'total_amt' => number_format($jo->poitems->poi_quantity * $jo->poitems->poi_unitprice,2),
      'jo' => $jo->jo_joborder,
      'jo_qty' => $jo->jo_quantity,
      'date_needed' => $jo->jo_dateneeded,
    );

    foreach($pr->pritems as $item){

      $masterlist = Masterlist::where('m_code',$item->pri_code)->first();

       array_push($prItems, array(
        'id' => $item->id,
        'code' => $item->pri_code,
        'mspecs' => $item->pri_mspecs,
        'unit' => $item->pri_uom,
        'quantity' => $item->pri_quantity,
        'date_needed' => $item->pr->jo->jo_dateneeded,
        'costing' => $masterlist ? $masterlist->m_supplierprice : 'No match record',
        'budgetPrice' => $masterlist ? $masterlist->m_budgetprice : 'No match record'
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
        'unitprice' => $price ? $price->pri_unitprice : 0     
      ));

    }

    return response()->json([
      'prPricing' => $prPricing
    ]);

  }

}
