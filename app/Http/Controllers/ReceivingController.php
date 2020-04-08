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

use App\PurchaseRequest;
use App\PurchaseOrderSupplier;
use App\PurchaseOrderSeries;
use App\ReceivingReportSeries;
use App\SupplierInvoice;
use App\Supplier;
use App\User;
use App\PurchaseOrderSupplierItems;

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
      'supplierOption' => $this->getSupplier(),
    ]);
  }

  public function viewPurchaseOrderItems($id){
    $po = PurchaseOrderSupplier::findOrFail($id);
    $poItems = $po->poitems()
      ->get()
      ->map(function($item) {
        return array(
          'id' => $item->id,
          'prnumber' => $item->spo->prprice()->first()->pr->pr_prnum,
          'code' => $item->spoi_code,
          'materialSpecification' => $item->spoi_mspecs,
          'unit' => $item->spoi_uom,
          'unitprice' => $item->spoi_unitprice,
          'quantity' => $item->spoi_quantity,
          'deliveryDate' => $item->spoi_deliverydate,
          'delivered' => intval($item->invoice()->sum('ssi_receivedquantity')),
        );
      })
      ->toArray();
    return response()->json([
      'poItems' => $poItems,
    ]);
  }

  public function getItemInvoices($id) {
    $invoiceList = SupplierInvoice::whereHas('poitem.spo', function($q) use ($id){
        return $q->where('id', $id);
      })
      ->where('ssi_rrnum',null)
      ->where('ssi_inspectedquantity',0)
      ->where('ssi_receivedquantity',0)
      ->get()
      ->map(function($invoice) {

        return array(
          'id' => $invoice->id,
          'code' => $invoice->poitem->spoi_code,
          'materialSpecification' => $invoice->poitem->spoi_mspecs,
          'invoice' => $invoice->ssi_invoice,
          'dr' => $invoice->ssi_dr,
          'quantity' => $invoice->ssi_drquantity,
          'underrun' => $invoice->ssi_underrunquantity,
        );
      })
      ->values();

      $series = ReceivingReportSeries::first();
      $number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
      $rrseries = $series->series_prefix.date('y'). "-".$number;

    return response()->json([
      'invoiceList' => $invoiceList,
      'rrseries' => $rrseries,
    ]);
  }

  public function addReceivingReport(Request $request) {
    $invoice = SupplierInvoice::findOrFail($request->invoiceID);
    $item = $invoice->poitem;
    $itemRemaining = $item->spoi_quantity - intval($item->invoice()->sum(Db::raw('ssi_receivedquantity + ssi_underrunquantity')) );
    $invoiceRemaining = $invoice->ssi_drquantity > $itemRemaining ? $itemRemaining : $invoice->ssi_drquantity; 

    if($invoice->ssi_rrnum != null || $invoice->ssi_inspectedquantity != 0 
      || $invoice->ssi_receivedquantity != 0)
      return response()->json(['errors' => ['Cannot add new receiving details to this invoice'] ], 422);

    $validator = Validator::make($request->all(),
      [
        'invoiceID' => 'integer|required',
        'rrseries' => 'string|unique:psms_supplierinvoice,ssi_rrnum',
        'inspectedQty' => 'integer|required|min:1|max:'.$invoiceRemaining,
        'receivedQty' => 'integer|required|min:1|max:'.$invoiceRemaining,
        'remarks' => 'string|max:250|nullable',
        'rejectQty' => 'integer|nullable|min:0|max:'.$invoiceRemaining,
        'rejectRemarks' => 'string|max:250|nullable',
      ],[],
      [
        'rrseries' => 'Receiving report number',
        'inspectedQty' => 'Inspected Quantity',
        'receivedQty' => 'Received Quantity',
        'rejectQty' => 'Rejected Quantity',
        'rejectRemarks' => 'Rejection Remarks',
      ]
    );

    $validator->sometimes('rejectRemarks','required' , function($input) {
      return $input->rejectQty > 0;
    });

    if($validator->fails())
        return response()->json(['errors' => $validator->errors()->all()] ,422);

    $invoice->update([
      'ssi_rrnum' => $request->rrseries,
      'ssi_inspectedquantity' => $request->inspectedQty,
      'ssi_receivedquantity' => $request->receivedQty,
      'ssi_remarks' => $request->remarks,
      'ssi_rejectquantity' => $request->rejectQty,
      'ssi_rejectionremarks' => $request->rejectRemarks,
    ]);

    ReceivingReportSeries::first()
      ->update(['series_number' => DB::raw('series_number + 1')]); //update series
    $po = $invoice->poitem->spo;
    $id = $po->id;
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
      'message' => 'Record added',
      'newPo' => $newPo,
    ]);

  }

}
