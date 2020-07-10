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
use App\JobOrder;

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
      GROUP_CONCAT(pr_prnum) as prNumbers
      FROM prms_prlist
      LEFT JOIN psms_prsupplierdetails prsd 
      ON prsd.prsd_pr_id = prms_prlist.id
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
        'prNumbers',
        'currency',
        'itemCount',
        'quantityDelivered',
        'totalPoQuantity',
        'spo_date as date'
      );

    if(request()->has('supplier')){
      $q->whereRaw('supplier.id = ?', array(request()->supplier));
    }
    $q->whereRaw('quantityDelivered < totalPoQuantity');
    $poList = $q->latest('psms_spurchaseorder.id')
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
    $invoiceItem = $invoice->poitem;
    $itemRemaining = $invoiceItem->spoi_quantity - intval($invoiceItem->invoice()->sum(Db::raw('ssi_receivedquantity + ssi_underrunquantity')) );
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
    $items = $po->poitems()->get();

    $itemCount = $items->count();
    $totalPoQuantity = $items->sum('spoi_quantity');
    $quantityDelivered = SupplierInvoice::whereHas('poitem.spo', function($q) 
      use ($id){
        return $q->where('id', $id);
      })
      ->sum('ssi_receivedquantity');
    //log 
    $this->logCreatingReceivedReport($po->spo_ponum, $request->rrseries,
      $invoiceItem->spoi_mspecs);
    $newPo = array(
      'id' => $po->id,
      'supplier' => $po->prprice()->first()->supplier->sd_supplier_name,
      'poNum' => $po->spo_ponum,
      'prNumbers' => $prNumber,
      'currency' => $po->prprice()->first()->prsd_currency,
      'itemCount' => $itemCount,
      'quantityDelivered' => $quantityDelivered,
      'totalPoQuantity' => $totalPoQuantity,
      'date' => $po->spo_date
    );

    return response()->json([
      'message' => 'Record added',
      'newPo' => $newPo,
    ]);

  }

  public function getRRList() {
    $limit = request()->has('recordCount') ? request()->recordCount : 500;

    $spo = Db::table('psms_spurchaseorder')
      ->select('id', 'spo_ponum')
      ->groupBy('id');

    $item = Db::table('psms_spurchaseorderitems')
      ->select('id','spoi_po_id', 'spoi_mspecs', 'spoi_code')
      ->groupBy('id');

    $prPrice = Db::table('psms_prsupplierdetails')
      ->select('prsd_spo_id', 'prsd_supplier_id')
      ->groupBy('prsd_spo_id');

    $supplier = Db::table('psms_supplierdetails')
      ->select('id', 'sd_supplier_name')
      ->groupBy('id');

    $q = SupplierInvoice::has('poitem.spo')
      ->leftJoinSub($item, 'item', function($join){
        return $join->on('psms_supplierinvoice.ssi_poitem_id', '=', 'item.id');
      })
      ->leftJoinSub($spo, 'spo', function($join){
        return $join->on('spo.id', '=', 'item.spoi_po_id');
      })
      ->leftJoinSub($prPrice, 'prPrice', function($join){
        return $join->on('prPrice.prsd_spo_id', '=', 'spo.id');
      })
      ->leftJoinSub($supplier, 'supplier', function($join){
        return $join->on('prPrice.prsd_supplier_id', '=', 'supplier.id');
      })
      ->select(
        'psms_supplierinvoice.id',
        Db::raw('IF(ssi_rejectquantity > 0, "W/ REJECT", "NO REJECT") as status'),
        'sd_supplier_name as supplier',
        'ssi_rrnum as rrNumber',
        'spo_ponum as poNum',
        'spoi_code as code',
        'spoi_mspecs as materialSpecification',
        'ssi_invoice as invoice',
        'ssi_dr as dr',
        'ssi_date as date',
        'ssi_drquantity as quantity',
        'ssi_inspectedquantity as inspectedQty',
        'ssi_receivedquantity as receivedQty',
        'ssi_remarks as remarks'
      )
      ->where('ssi_rrnum','!=', null)
      ->where('ssi_receivedquantity','!=', 0)
      ->where('ssi_inspectedquantity','!=', 0);

    if(request()->has('supplier')){
      $q->whereRaw('supplier.id = ?', array(request()->supplier));
    }

    $rrList = $q->latest('psms_supplierinvoice.id')
      ->limit($limit)
      ->get();

    return response()->json([
      'rrList' => $rrList,
      'supplierOption' => $this->getSupplier(),
    ]);
  }

  public function removeRRfromInvoice($id) {
    $invoice = SupplierInvoice::findOrFail($id);
    $rrSeries = $invoice->ssi_rrnum;
    $invoice->update([
      'ssi_rrnum' => NULL,
      'ssi_receivedquantity' => 0,
      'ssi_inspectedquantity' => 0,
      'ssi_rejectquantity' => NULL,
      'ssi_rejectionremarks' => NULL,
    ]);
    $this->logDeleteingReceivedReport($rrSeries, $invoice->poitem->spo->spo_ponum);
    return response()->json([
      'message' => 'Record removed',
    ]);
  }

  public function printRR($id) {
    $rr = SupplierInvoice::findOrFail($id);

    $item = $rr->poitem;
    $po = $rr->poitem->spo;
    if(!$rr->ssi_rrnum)
      return response()->json(['errors' => ['No receiving report available!']], 422);

    $jobOrders = JobOrder::whereHas('pr.prpricing.po.poitems.invoice', function($q) use ($id){
      $q->where('id', $id);
    })
    ->get()
    ->pluck('jo_joborder')
    ->toArray();

    $rrDetails = (object) array(
      'rrNum' => $rr->ssi_rrnum,
      'itemDescription' => $item->spoi_mspecs,
      'supplier' => $po->prprice()->first()->supplier->sd_supplier_name,
      'poNum' => $po->spo_ponum,
      'jo' => implode(",",$jobOrders),
      'drQty' => $rr->ssi_drquantity,
      'inspectedQty' => $rr->ssi_inspectedquantity,
      'receivedQty' => $rr->ssi_receivedquantity,
      'arrivalDate' => $rr->ssi_date,
      'drNum' => $rr->ssi_dr,
      'invoice' => $rr->ssi_invoice,
      'rejectQty' => $rr->ssi_rejectquantity,
      'rejectRemarks' => $rr->ssi_rejectionremarks,
    );

    $pdf =  PDF::loadView('wrms.printreceivingreport', compact('rrDetails'))->setPaper('a4','landscape');
    return $pdf->stream();
  }

  public function printRTV($id) {
    $rr = SupplierInvoice::findOrFail($id);
    $po = $rr->poitem->spo;
    if(!$rr->ssi_rrnum || !$rr->ssi_rejectquantity || $rr->ssi_rejectquantity < 1)
      return response()->json(['errors' => ['No rtv available!']], 422);

    $rrDetails = (object) array(
      'rrNum' => $rr->ssi_rrnum,
      'itemDescription' => $rr->poitem->spoi_mspecs,
      'supplier' => $po->prprice()->first()->supplier->sd_supplier_name,
      'poNum' => $po->spo_ponum,
      'drQty' => $rr->ssi_drquantity,
      'arrivalDate' => $rr->ssi_date,
      'drNum' => $rr->ssi_dr,
      'invoice' => $rr->ssi_invoice,
      'rejectQty' => $rr->ssi_rejectquantity,
      'rejectRemarks' => $rr->ssi_rejectionremarks,
    );

    $pdf =  PDF::loadView('wrms.printrtvreport', compact('rrDetails'))->setPaper('a4','portrait');
    return $pdf->stream();
  }

}
