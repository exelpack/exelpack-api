<?php

namespace App\Exports;

use App\PurchaseOrder;
use App\PurchaseOrderDelivery;
use DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseOrderExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
    	$pod = DB::table('cposms_poitemdelivery')->select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
      as totalDelivered'),'poidel_item_id')->groupBy('poidel_item_id');

      $poi = DB::table('cposms_purchaseorderitem')->select('id', 'poi_po_id',
        DB::raw('count(*) as totalItems'), DB::raw('sum(poi_quantity) as totalQuantity'))
        ->groupBy('poi_po_id');

      $customer = DB::table('customer_information')->select('id', 'companyname')
        ->groupBy('id');

      $jo = DB::table('pjoms_joborder')->select('jo_po_item_id', DB::raw('count(*) as joCount'))
        ->groupBy('jo_po_item_id');

      $q = PurchaseOrder::leftJoinSub($poi, 'poi', function($join){
          $join->on('cposms_purchaseorder.id','=','poi.poi_po_id');    
        })
        ->leftJoinSub($jo, 'jo', function($join){
          $join->on('poi.id','=','jo.jo_po_item_id');    
        })
        ->leftJoinSub($pod, 'delivery',function ($join){
          $join->on('poi.id','=','delivery.poidel_item_id');       
        })
        ->leftJoinSub($customer, 'customer', function($join){
          $join->on('customer.id','=','cposms_purchaseorder.po_customer_id');    
        });

      $q->select([
        'po_ponum as po_num',
        'customer.companyname as customerLabel',
        'po_date as date',
        'po_currency as currency',
        DB::raw('IFNULL(poi.totalItems,0) as totalItems'),
        DB::raw('IFNULL(poi.totalQuantity,0) as totalQuantity'),
        DB::raw('IFNULL(delivery.totalDelivered,0) as totalDelivered'),
        DB::raw('IF(IFNULL(delivery.totalDelivered,0) >= poi.totalQuantity,"SERVED","OPEN") as status'),
        
      ]);

      if(request()->has('customer')){
        $q->whereRaw('customer.id = ?', array(request()->customer));
      }

      if(request()->has('status')){
        $status = strtolower(request()->status);
        if($status == 'served')
          $q->whereRaw('IFNULL(delivery.totalDelivered,0) >= poi.totalQuantity');
        else
          $q->whereRaw('IFNULL(delivery.totalDelivered,0) < poi.totalQuantity');
      }

      if(request()->has('month')){
        $q->whereMonth('po_date' , request()->month);
      }

      if(request()->has('year')){
        $q->whereYear('po_date', request()->year);
      }

      if(request()->has('search')){
      $q->where('po_ponum', 'LIKE' ,'%'.request()->search.'%');
    }

    $sort = strtolower(request()->sort) ?? '';
    if($sort == 'date-asc')
      $q->orderBy('po_date', 'ASC');
    else if($sort == 'date-desc')
      $q->orderBy('po_date', 'DESC');
    else if($sort == 'latest')
      $q->latest('cposms_purchaseorder.id');
    else if($sort == 'oldest')
      $q->oldest('cposms_purchaseorder.id');

    $po = $q->latest()
      ->get()
      ->toArray();

    	return $po;
    }

    public function headings(): array
    {
    	return [
    		'PURCHASE ORDER NO.',
    		'CUSTOMER',
    		'DATE',
    		'CURRENCY',
    		'NO. OF ITEMS',
    		'TOTAL PO QUANTITY',
    		'TOTAL DELIVERED QTY',
    		'STATUS'
    	];
    }
  }
