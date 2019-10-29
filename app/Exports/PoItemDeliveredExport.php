<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\PurchaseOrderDelivery;

class PoItemDeliveredExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array() :array
    {
    	$q = PurchaseOrderDelivery::query();

    	if(request()->has('search')){
    		$search = request()->search;
    		$q->whereHas('item.po' ,function($q) use ($search) {
    			$q->where('po_ponum','LIKE','%'.$search.'%');
    		})
    		->orWhereHas('item', function($q) use ($search){
    			$q->where('poi_itemdescription','LIKE','%'.$search.'%');
    		})
    		->orWhere('poidel_dr','LIKE','%'.$search.'%')
    		->orWhere('poidel_invoice','LIKE','%'.$search.'%');
    	}

    	if(request()->has('start') && request()->has('end')){
    		$start = request()->start;
    		$end = request()->end;

    		$q->whereBetween('poidel_deliverydate',[$start,$end]);
    	}
    	$q->has('item.po');
    	$q->orderBy('poidel_deliverydate','desc');
    	$deliveries_result = $q->get();

    	$deliveredItems = $this->getDeliveries($deliveries_result);
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

    public function getDeliveries($deliveries) //convert deliveries
    {
    	$deliveries_arr = array();

    	foreach($deliveries as $row){
    		array_push($deliveries_arr,$this->getDelivery($row));
    	}

    	return $deliveries_arr;
    }

		public function getDelivery($delivery) //convert delivery
		{

			return array(
				'customer' => $delivery->item->po->customer->companyname,
				'po_num' => $delivery->item->po->po_ponum,
				'item_desc' => $delivery->item->poi_itemdescription,
				'quantity' => $delivery->poidel_quantity,
				'underrun' => $delivery->poidel_underrun_qty,
				'date' => $delivery->poidel_deliverydate,
				'invoice' => $delivery->poidel_invoice,
				'dr' => $delivery->poidel_dr,
				'remarks' => $delivery->poidel_remarks,
			);

		}
	}
