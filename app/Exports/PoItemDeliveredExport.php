<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\PurchaseOrderDelivery;
use DB;

class PoItemDeliveredExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array() :array
    {
    	$recordCount = request()->has('recordCount') ? request()->recordCount : 500;
      $item = DB::table('cposms_purchaseorderitem')->select(
        'poi_itemdescription',
        'poi_partnum',
        'poi_code',
        'poi_po_id',
        'id'
      );

      $po = DB::table('cposms_purchaseorder')->select(
        'id',
        'po_ponum',
        'po_customer_id'
      );

      $customer = DB::table('customer_information')->select(
        'id',
        'companyname'
      ); 

      $q = PurchaseOrderDelivery::has('item.po')
        ->leftJoinSub($item, 'item', function($join){
          $join->on('cposms_poitemdelivery.poidel_item_id','=','item.id');
        })
        ->leftJoinSub($po, 'po', function($join){
          $join->on('item.poi_po_id','=','po.id');
        })
        ->leftJoinSub($customer, 'customer', function($join){
          $join->on('po.po_customer_id','=','customer.id');
        })
        ->select(
          'companyname as customer',
          'po.po_ponum as po_num',
          'item.poi_itemdescription as item_desc',
          'poidel_quantity as quantity',
          'poidel_underrun_qty as underrun',
          'poidel_deliverydate as date',
          'poidel_invoice as invoice',
          'poidel_dr as dr',
          'poidel_remarks as remarks'
        );

      if(request()->has('start') && request()->has('end')){
        $start = request()->start;
        $end = request()->end;
        $q->whereBetween('poidel_deliverydate',[$start,$end]);
      }
      $deliveredItems = $q->latest('cposms_poitemdelivery.id')
        ->limit($recordCount)
        ->get()->toArray();
    	return $deliveredItems;
    }


    public function headings():array
    {
    	return [
    		'CUSTOMER',
    		'PURCHASE ORDER NO.',
    		'ITEM DESC',
    		'QUANTITY',
    		'UNDERRUN',
    		'DATE',
    		'INVOICE',
    		'DELIVERY RECEIPT',
    		'REMARKS',
    	];
    }

	}
