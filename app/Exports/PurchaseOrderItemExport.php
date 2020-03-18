<?php

namespace App\Exports;

use App\PurchaseOrderDelivery;
use App\PurchaseOrderItems;
use DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrderItemExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
  public function array(): array
  {

  	$pod = DB::table('cposms_poitemdelivery')->select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
      as totalDelivered'),'poidel_item_id',DB::raw('count(*) as deliveryCount'))->groupBy('poidel_item_id');

    $pos = DB::table('cposms_podeliveryschedule')->select(DB::raw('count(*) as schedCount'),'pods_item_id')
        ->groupBy('pods_item_id');

    $po = DB::table('cposms_purchaseorder')->select('id', 'po_ponum', 'po_currency', 'po_customer_id')
      ->groupBy('id');

    $customer = DB::table('customer_information')->select('id', 'companyname')
      ->groupBy('id');

    $q = PurchaseOrderItems::has('po')
      ->leftJoinSub($pod, 'delivery',function ($join){
        $join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');       
      })
      ->leftJoinSub($pos, 'sched', function($join){
        $join->on('sched.pods_item_id', '=', 'cposms_purchaseorderitem.id');
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('po.id','=','cposms_purchaseorderitem.poi_po_id');    
      })
      ->leftJoinSub($customer, 'customer', function($join){
        $join->on('customer.id','=','po.po_customer_id');    
      });

    $q->select([
      'customer.companyname as customer',
      'po.po_ponum as po_num',
      'poi_code as code',
      'poi_partnum as partnum',
      'poi_itemdescription as itemdesc',
      'poi_quantity as quantity',
      'poi_unit as unit',
      'po.po_currency as currency',
      'poi_unitprice as unitprice',
      'poi_deliverydate as deliverydate',
      'poi_kpi as kpi',
      DB::raw('IFNULL(delivery.totalDelivered,0) as delivered_qty'),
      'poi_remarks as remarks',
      'poi_others as others',
      DB::raw('IF(IFNULL(delivery.totalDelivered,0) >= cposms_purchaseorderitem.poi_quantity,"SERVED","OPEN") as status'),

    ]);

    if(request()->has('customer')){
      $q->whereRaw('customer.id = ?', array(request()->customer));
    }

    if(request()->has('status')){
      $status = strtolower(request()->status);
      if($status == 'served')
        $q->whereRaw('IFNULL(delivery.totalDelivered,0) >= poi_quantity');
      else
        $q->whereRaw('IFNULL(delivery.totalDelivered,0) < poi_quantity');
    }

    if(request()->has('deliveryDue')){
      $dateFilter = Carbon::now()->addDays(request()->deliveryDue);
      $q->whereDate('poi_deliverydate','<=', $dateFilter);
    }

    $poItems = $q->latest()
      ->limit(request()->has('recordCount') ? request()->recordCount : 500)
      ->get()
      ->toArray();

		return $poItems;
	}	

	public function headings(): array
  {
  	return [
  		'CUSTOMER',
  		'PURCHASE ORDER NO.',
  		'CODE',
  		'PART NUMBER',
  		'ITEM DESC',
  		'QUANTITY',
  		'UNIT',
  		'CURRENCY',
  		'UNIT PRICE',
  		'DELIVERY DATE',
  		'KPI',
  		'DELIVERED QTY',
  		'REMARKS',
      'OTHERS',
  		'STATUS',
  	];
  }
}
