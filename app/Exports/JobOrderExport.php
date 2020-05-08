<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\JobOrderProduced;
use App\JobOrder;
use App\Customers;
use App\PurchaseOrderItems;
use App\PurchaseOrder;
use DB;
class JobOrderExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
    	$sort = strtolower(request()->sort);
      $showRecord = strtolower(request()->showRecord);

      $jobOrder = JobOrder::select(Db::raw('sum(jo_quantity) as totalJobOrderQty'),'jo_po_item_id')
        ->groupBy('jo_po_item_id');

      $purchaseOrderItem = PurchaseOrderItems::select(
          'id',
          'poi_po_id',
          'poi_itemdescription',
          'poi_code',
          'poi_quantity'
        )->groupBy('id');

      $purchaseOrder = PurchaseOrder::select('id', 'po_customer_id','po_ponum', 'po_currency')
        ->groupBy('id');

      $customer = Customers::select('id','companyname')
        ->groupBy('id');

      $produced = JobOrderProduced::select(Db::raw('sum(jop_quantity) as producedQty'),
        'jop_jo_id')->groupBy('jop_jo_id');

      $pr = Db::table('prms_prlist')->select(
          Db::raw('count(*) as prCount'),
          'pr_jo_id'
        )->groupBy('pr_jo_id');

      $q = JobOrder::has('poitems.po');
      // join
      $q->leftJoinSub($produced, 'prod', function($join){
        $join->on('prod.jop_jo_id','=','pjoms_joborder.id');
      })->leftJoinSub($purchaseOrderItem, 'item', function($join){
        $join->on('item.id','=','pjoms_joborder.jo_po_item_id');
      })->leftJoinSub($jobOrder, 'jo', function($join){
        $join->on('jo.jo_po_item_id','=','item.id');
      })->leftJoinSub($purchaseOrder, 'po', function($join){
        $join->on('po.id','=','item.poi_po_id');
      })->leftJoinSub($customer, 'customer', function($join){
        $join->on('customer.id','=','po.po_customer_id');
      })->leftJoinSub($pr, 'pr', function($join){
        $join->on('pr.pr_jo_id','=','pjoms_joborder.id');
      });
      //select
      $q->select(
        Db::raw('IF(jo_quantity > IFNULL(producedQty,0),"OPEN","SERVED" ) as status'),
        'jo_joborder as jo_num',
        'po_ponum as poNum',
        'companyname as customer',
        'jo_dateissued as date_issued',
        'jo_dateneeded as date_needed',
        'poi_code as code',
        'poi_itemdescription as itemDesc',
        'jo_quantity as quantity',
        Db::raw('IFNULL(producedQty,0) as producedQty'),
        'jo_remarks as remarks',
        'jo_others as others'
      );

      if(request()->has('search')){

        $search = "%".strtolower(request()->search)."%";

        $q->whereHas('poitems.po', function($q) use ($search){
          $q->where('po_ponum','LIKE', $search);
        })->orWhereHas('poitems', function($q) use ($search){
          $q->where('poi_itemdescription','LIKE',$search);
        })->orWhere('jo_joborder','LIKE',$search);

      }

      if($showRecord == 'open')
        $q->whereRaw('jo_quantity > IFNULL(producedQty,0)');
      else if($showRecord == 'served')
        $q->whereRaw('jo_quantity <= IFNULL(producedQty,0)');

      if(request()->has('customer')) {
        $q->whereRaw('companyname = ?',array(request()->customer));
      }

      if(request()->has('month')) {
        $q->whereMonth('jo_dateissued',request()->month);
      }

      if(request()->has('year')) {
        $q->whereYear('jo_dateissued',request()->year);
      }

      if($sort == 'desc'){
        $q->orderBy('pjoms_joborder.id','DESC');
      }else if($sort == 'asc'){
        $q->orderBy('pjoms_joborder.id','ASC');
      }else if($sort == 'di-desc'){
        $q->orderBy('jo_dateissued','DESC');
      }else if($sort == 'di-asc'){
        $q->orderBy('jo_dateissued','ASC');
      }else if($sort == 'jo-desc'){
        $q->orderBy('jo_joborder','DESC');
      }else if($sort == 'jo-asc'){
        $q->orderBy('jo_joborder','ASC');
      }

      return $q->get()->toArray();
    }

    public function headings(): array
    {
    	return [
    		'STATUS',
    		'JOB ORDER',
    		'PURCHASE ORDER',
    		'CUSTOMER',
    		'DATE ISSUED',
    		'DATE NEEDED',
    		'CODE',
    		'PART NUMBER',
    		'ITEM DESCRIPTION',
    		'QUANTITY',
    		'PRODUCED QUANTITY',
    		'REMARKS',
    		'OTHERS',
    	];
    }

  }
