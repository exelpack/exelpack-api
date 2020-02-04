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
    $pageSize = request()->pageSize;
    $pr = PurchaseRequest::where('pr_forPricing',1)
      ->paginate($pageSize);
    $prList = $pr->map(function($pr) {

        return array(
          'id' => $pr->id,
          'status' => $pr->prpricing()->count() > 0 ? 'W/ PRICE' : 'PENDING',
          'po_num' => $pr->jo->poitems->po->po_ponum,
          'jo_num' => $pr->jo->jo_joborder,          
          'pr_num' => $pr->pr_prnum,
          'date' => $pr->pr_date,
          'remarks' => $pr->pr_remarks,
          'itemCount' => $pr->pritems->count(),
        );
      });

    return response()->json([
      'prListLength' => $pr->total(),
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
