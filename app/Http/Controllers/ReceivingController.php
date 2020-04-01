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
use Carbon\Carbon;

use App\PurchaseRequestApproval;
use App\PurchaseOrderSupplier;
use App\PurchaseRequestSupplierDetails;
use App\PurchaseOrderSeries;
use App\PurchaseRequest;
use App\PurchaseRequestItems;
use App\SupplierInvoice;
use App\Masterlist;
use App\Supplier;
use App\User;

class ReceivingController extends LogsController
{
  public function getSupplier(){
    $supplier = Supplier::select('id', 'sd_supplier_name as supplierName')
                  ->orderBy('sd_supplier_name','ASC')
                  ->get();
    return $supplier;
  }

  public function getPurchaseOrder(){
    $limit = request()->has('recordCount') ? request()->recordCount : 500;

    $supplier = DB::table('psms_supplierdetails')
      ->select('id','sd_supplier_name')
      ->groupBy('id');
    $joinQry = "SELECT
      prms_prlist.id,
      prsd.prsd_supplier_id as supplier_id,
      prsd.prsd_spo_id,
      prsd.prsd_currency as currency,
      GROUP_CONCAT(pr_prnum) as prnumbers,
      CAST(sum(itemCount) as int) as itemCount,
      CAST(sum(totalPrQuantity) as int) as totalPoQuantity,
      IFNULL(CAST(sum(quantityDelivered) as int),0) as quantityDelivered,
      IFNULL(CAST(sum(invoiceCount) as int),0) as invoiceCount
      FROM prms_prlist
      LEFT JOIN psms_prsupplierdetails prsd 
      ON prsd.prsd_pr_id = prms_prlist.id
      LEFT JOIN (SELECT count(*) as itemCount,
      SUM(pri_quantity) as totalPrQuantity,pri_pr_id,id
      FROM prms_pritems
      GROUP BY pri_pr_id) pri
      ON pri.pri_pr_id = prms_prlist.id
      LEFT JOIN (SELECT count(*) as invoiceCount,
      SUM(ssi_receivedquantity + ssi_underrunquantity) as quantityDelivered,ssi_pritem_id 
      FROM psms_supplierinvoice
      WHERE ssi_receivedquantity > 0
      GROUP BY ssi_pritem_id) prsi
      ON prsi.ssi_pritem_id = pri.id
      GROUP BY prsd_spo_id";

    $q = PurchaseOrderSupplier::has('prprice.pr.jo.poitems.po')
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
      $q->whereRaw('invoiceCount > 0');
      $poList = $q->latest('psms_spurchaseorder.id')->limit($limit)->get();

    return response()->json([
      'poList' => $poList,
    ]);
  }

  public function viewPurchaseOrderItems($id){
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
          'deliveryDate' => $item->pri_deliverydate,
          'delivered' => intval($item->invoice()->sum('ssi_receivedquantity')),
        );
      })
      ->toArray();

    return response()->json([
      'poItems' => $poItems,
    ]);
  }

  public function getItemInvoices($id) {
    $invoices = SupplierInvoice::whereHas('pritem.pr.prpricing.po', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->where('ssi_rrnum',null)
      ->where('ssi_inspectedquantity',0)
      ->where('ssi_receivedquantity',0)
      ->get()
      ->map(function($invoice) {

        return array(
          'id' => $invoice->id,
          
        );
      })

  }
}
