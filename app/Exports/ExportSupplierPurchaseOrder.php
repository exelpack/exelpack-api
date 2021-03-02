<?php

namespace App\Exports;

use DB;
use App\PurchaseOrderSupplier;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportSupplierPurchaseOrder implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
      $limit = request()->has('recordCount') ? request()->recordCount : 1000;

      $joinQry = "SELECT
        prms_prlist.id,
        prsd.prsd_supplier_id as supplier_id,
        prsd.prsd_spo_id,
        prsd.prsd_currency as currency,
        GROUP_CONCAT(pr_prnum) as prnumbers,
        CAST(sum(itemCount) as SIGNED) as itemCount,
        CAST(sum(totalPrQuantity) as SIGNED) as totalPoQuantity,
        IFNULL(CAST(sum(quantityDelivered) as SIGNED),0) as quantityDelivered
        FROM prms_prlist
        LEFT JOIN psms_prsupplierdetails prsd 
        ON prsd.prsd_pr_id = prms_prlist.id
        LEFT JOIN (SELECT count(*) as itemCount,
        SUM(pri_quantity) as totalPrQuantity,pri_pr_id,id
        FROM prms_pritems
        GROUP BY pri_pr_id) pri
        ON pri.pri_pr_id = prms_prlist.id
        LEFT JOIN (SELECT SUM(ssi_receivedquantity + ssi_underrunquantity) as quantityDelivered,ssi_poitem_id 
        FROM psms_supplierinvoice
        WHERE ssi_receivedquantity > 0
        GROUP BY ssi_poitem_id) prsi
        ON prsi.ssi_poitem_id = pri.id
        GROUP BY prsd_spo_id";

      $supplier = DB::table('psms_supplierdetails')
        ->select('id','sd_supplier_name')
        ->groupBy('id');

      $itemsTbl = DB::table('prms_pritems')
        ->select('pri_pr_id',DB::raw('count(*) as itemCount'))
        ->groupBy('pri_pr_id');

      $q = PurchaseOrderSupplier::has('prprice.pr.jo.poitems.po')
        ->leftJoinSub($joinQry,'pr', function($join){
          $join->on('pr.prsd_spo_id','=','psms_spurchaseorder.id');
        })
        ->leftJoinSub($supplier,'supplier', function($join){
          $join->on('supplier.id','=','pr.supplier_id');
        })
        ->select(
          'sd_supplier_name as supplier',
          'spo_ponum as poNum',
          'prnumbers',
          'currency',
          'totalPoQuantity',
          'quantityDelivered',
          'spo_date as date',
          Db::raw('IF(spo_sentToSupplier = 0 && quantityDelivered < 1,
            "PENDING",
            IF(quantityDelivered > 0,
              IF(quantityDelivered >= totalPoQuantity,
                "DELIVERED",
                "PARTIAL"
              ),
              "OPEN"
            )
          ) as status')
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

      $poList = $q->orderBy('psms_spurchaseorder.id','DESC')
      ->limit($limit)
      ->get()
      ->toArray();

      return $poList;
    }

    public function headings(): array
    {
      return [
        'SUPPLIER',
        'PURCHASE ORDER',
        'P.R NUMBER/S',
        'CURRENCY',
        'TOTAL QUANTITY',
        'TOTAL DELIVERED',
        'DATE',
        'STATUS',
      ];
    }
}
