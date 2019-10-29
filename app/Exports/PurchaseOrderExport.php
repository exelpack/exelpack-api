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
    	$sub = PurchaseOrderDelivery::select('poidel_item_id',
    		Db::raw('IFNULL(sum(poidel_quantity + poidel_underrun_qty),0) as totalDelivered'));

    	$q = PurchaseOrder::query();
		//filter
    	if(request()->has('status')){
    		if(request()->status === 'NO ITEM'){
    			$q->whereDoesntHave('poitems');
    		}else{

    			$fStatus = request()->status;
				//check if with items
    			$q->whereHas('poitems' , function($q1) use ($sub,$fStatus){
    				$q1->from('cposms_purchaseorderitem')
					->leftJoinSub($sub,'delivery', function($join){ //ljoin delivery to get total delivered qty
						$join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');
					})
					->select(Db::raw('sum(poi_quantity) as totalItemQty'),
						DB::raw('IFNULL(delivery.totalDelivered,0) as totalDelivered'));

					if($fStatus === 'OPEN')
						$q1->havingRaw('totalItemQty > totalDelivered');
					else
						$q1->havingRaw('totalDelivered >= totalItemQty');

				});
    		}
    	}

    	if(request()->has('customer')){
    		$q->where('po_customer_id',request()->customer);
    	}

    	if(request()->has('month')){
    		$q->whereMonth('po_date',request()->month);
    	}

    	if(request()->has('year')){
    		$q->whereYear('po_date',request()->year);
    	}

    	if(request()->has('po')){
    		$q->where('po_ponum','LIKE','%'.request()->po.'%');
    	}

    	if(request()->has('sortDate')){
    		$q->orderBy('po_date',request()->sortDate);
    	}
		// end filter
    	$po_result = $q->get();	
    	$po = $this->getPos($po_result);

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


    public function getPo($po)
    {
    	$totalQuantity = $po->getTotalItemQuantity->totalQuantity;
    	$totalDelivered = intval($po->getTotalDeliveryQuantity->totalDelivered);
    	$status = $po->poitems()->count() > 0 ? $totalDelivered >= $totalQuantity 
    	? 'SERVED' : 'OPEN' : 'NO ITEM';
    	return array(
    		'po_num' => $po->po_ponum,
    		'customer_label' => $po->customer->companyname,
    		'date' => $po->po_date,
    		'currency' => $po->po_currency,
    		'totalItems'=> $po->poitems()->count(),
    		'totalQuantity'=> $totalQuantity,
    		'totalDelivered'=> $totalDelivered,
    		'status' => $status,
    	);

    }

    public function getPos($pos)
    {
    	$po_arr = array();

    	foreach($pos as $row){
    		array_push($po_arr,$this->getPo($row));
    	}

    	return $po_arr;
    }

  }
