<?php

namespace App\Exports;

use DB;
use App\PurchaseRequestItems;
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

      $pr = Db::table('prms_prlist')
        ->select(
          'id',
          'pr_prnum as prNum'
        );

      $prPrice = Db::table('psms_prsupplierdetails')
        ->select(
          'prsd_pr_id',
          'prsd_spo_id',
          'prsd_supplier_id',
          'prsd_currency as currency'
        );

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
          'ssi_pritem_id',
          DB::raw('count(*) as invoiceCount'),
          DB::raw('CAST(SUM(ssi_receivedquantity + ssi_underrunquantity) as int) as totalDelivered'),
          DB::raw('CAST(SUM(ssi_drquantity) as int) as totalInvoiceQty')
        )
        ->groupBy('ssi_pritem_id');

      $q = PurchaseRequestItems::has('pr.prpricing.po')
        ->leftJoinSub($pr, 'pr', function($join){
          $join->on('prms_pritems.pri_pr_id','=','pr.id');
        })
        ->leftJoinSub($prPrice, 'prprice', function($join){
          $join->on('pr.id','=','prprice.prsd_pr_id');
        })
        ->leftJoinSub($po, 'po', function($join){
          $join->on('po.id','=','prprice.prsd_spo_id');
        })
        ->leftJoinSub($invoice, 'invoice', function($join){
          $join->on('prms_pritems.id','=','invoice.ssi_pritem_id');
        })
        ->leftJoinSub($supplier, 'supplier', function($join){
          $join->on('prprice.prsd_supplier_id','=','supplier.id');
        })
        ->select(
          'poNum',
          'prNum',
          'supplier',
          'pri_code as code',
          'pri_mspecs as materialSpecification',
          'pri_unitprice as unitPrice',
          'pri_quantity as quantity',
          'currency',
          DB::raw('(pri_unitprice * pri_quantity) as totalAmount'),
          DB::raw('IFNULL(totalDelivered,0) as totalDelivered'),
          Db::raw('(pri_quantity - IFNULL(totalDelivered,0)) as remaining'),
          'pri_deliverydate as deliverydate',
          DB::raw('
            IF(IFNULL(totalDelivered,0) >= pri_quantity,
              "DELIVERED",
              "OPEN") as status
          ')
        );

      if(request()->has('poItemStatus')){
        $status = strtolower(request()->poItemStatus);  
        if($status == 'open')
          $q->whereRaw('pri_quantity > IFNULL(totalDelivered,0)');
        else if($status == 'delivered')
          $q->whereRaw('IFNULL(totalDelivered,0) >= pri_quantity');
      }

      if(request()->has('supplier')){
        $q->whereRaw('supplier.id = ?', array(request()->supplier));
      }

      if(request()->has('currency')){
        $q->whereRaw('currency = ?', array(request()->currency));
      }

      if(request()->has('month')){
        $q->whereMonth('pri_deliverydate', request()->month);
      }

      if(request()->has('year')){
        $q->whereYear('pri_deliverydate', request()->year);
      }

      $poItems = $q->latest('prms_pritems.id')
      ->limit($limit)
      ->get()
      ->toArray();

      return $poItems;
    }

    public function headings(): array
    {
      return [
        'PURCHASE ORDER',
        'P.R NUMBER/S',
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

