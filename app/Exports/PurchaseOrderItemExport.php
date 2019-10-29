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

  	$q = PurchaseOrderItems::query();
		$q->has('po'); //fetch item with po only

		$sub = PurchaseOrderDelivery::select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
			as totalDelivered'),'poidel_item_id')->groupBy('poidel_item_id');
		// filter
		if(request()->has('status')){
			$q->from('cposms_purchaseorderitem')
			->leftJoinSub($sub,'delivery',function ($join){
				$join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');				
			});
			if(request()->status === 'OPEN')
				$q->whereRaw('poi_quantity > IFNULL(totalDelivered,0)');
			else
				$q->whereRaw('poi_quantity <= IFNULL(totalDelivered,0)');
		}

		if(request()->has('customer')){
			$fCustomer = request()->customer;
			$q->whereHas('po', function($q1) use ($fCustomer){
				$q1->where('po_customer_id',$fCustomer);
			});
		}

		if(request()->has('deliveryDue')){
			$date = Carbon::now()->addDays(request()->deliveryDue)->format('Y-m-d');
			$q->whereDate('poi_deliverydate','<=',$date);
		}

		if(request()->has('po')){
			$fPo = request()->po;
			$q->whereHas('po', function($q1) use ($fPo){
				$q1->where('po_ponum','LIKE','%'.$fPo.'%');
			});
		}

		if(request()->has('sortDate')){
			$q->orderBy('poi_po_id','desc')->orderBy('poi_deliverydate',request()->sortDate);
		}

		// end filter
		$poItems_result = $q->get();
		$poItems = $this->getItems($poItems_result);

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
    		'OTHERS',
    		'DELIVERY QTY',
    		'REMARKS',
    		'STATUS',
    	];
    }


	public function getItems($items)
	{
		$items_arr = array();

		foreach($items as $row){
			array_push($items_arr,$this->getItem($row));
		}

		return $items_arr;
	}

	public function getItem($item)
	{
		$delivered = $item->delivery()->sum(DB::raw('poidel_quantity + poidel_underrun_qty'));
		$status = $delivered >= $item->poi_quantity ? 'SERVED' : 'OPEN';

		return array(
			'customer' => $item->po->customer->companyname,
			'po_num' => $item->po->po_ponum,
			'code' => $item->poi_code,
			'partnum' => $item->poi_partnum,
			'itemdesc' => $item->poi_itemdescription,
			'quantity' => $item->poi_quantity,
			'unit' => $item->poi_unit,
			'currency' => $item->po->po_currency,
			'unitprice' => $item->poi_unitprice,
			'deliverydate' => $item->poi_deliverydate,
			'kpi' => $item->poi_kpi,
			'others' => $item->poi_others,
			'delivered_qty' => $delivered,
			'remarks' => $item->poi_remarks,
			'status' => $status
		);

	}
}
