<?php

namespace App\Exports;

use DB;
use App\PurchaseOrderSupplierItems;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportSupplierPurchaseOrderItems implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
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
        DB::raw('CAST(SUM(ssi_receivedquantity + ssi_underrunquantity) as SIGNED) as totalDelivered'),
        DB::raw('CAST(SUM(ssi_drquantity) as SIGNED) as totalInvoiceQty')
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
        'poNum',
        'supplier',
         DB::raw('spoi_code as code'),
        'spoi_mspecs as materialSpecification',
        'spoi_unitprice as unitPrice',
        'spoi_quantity as quantity',
        'currency',
        DB::raw('CAST((spoi_unitprice * spoi_quantity) as SIGNED) as totalAmount'),
        DB::raw('CAST(IFNULL(totalDelivered,0) as SIGNED) as totalDelivered'),
        Db::raw('CAST((spoi_quantity - IFNULL(totalDelivered,0)) as SIGNED) as remaining'),
        'spoi_deliverydate as deliverydate',
        DB::raw('
          IF(IFNULL(totalDelivered,0) >= spoi_quantity,
            "DELIVERED",
            "OPEN") as status
        ')
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

      $poItems = $q->latest('psms_spurchaseorderitems.id')
      ->limit($limit)
      ->get()
      ->toArray();

      return $poItems;
    }

    public function headings(): array
    {
      return [
        'PURCHASE ORDER',
        'SUPPLIER',
        'CODE',
        'MATERIAL SPECIFICATION',
        'UNIT PRICE',
        'QUANTITY',
        'CURRENCY',
        'TOTAL AMOUNT',
        'TOTAL DELIVERED',
        'OPEN PO',
        'DELIVERY DATE',
        'STATUS',
      ];
     
    }
}

