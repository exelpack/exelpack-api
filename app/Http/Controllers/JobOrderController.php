<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LogsController;

use App\PurchaseOrderItems;
use App\PurchaseOrderDelivery;
use App\JobOrder;
use App\JobOrderProduced;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

class JobOrderController extends LogsController
{

	public function getPoItem($item)
	{

		$qtyWithoutJo = $item->poi_quantity - $item->totalJo;
		return array(
			'id' => $item->id,
			'id' => $item->id,
			'customer' => $item->po->customer->companyname,
			'code' => $item->poi_code,
			'partnum' => $item->poi_partnum,
			'itemdesc' => $item->poi_itemdescription,
			'quantity' => $item->poi_quantity,
			'unit' => $item->poi_unit,
			'currency' => $item->po->po_currency,
			'unitprice' => $item->poi_unitprice,
			'kpi' => $item->poi_kpi,
			'qtyWithoutJo' => $qtyWithoutJo,
			'others' => $item->poi_others,
		);

	}

	public function getPoItems($items)
	{

		$items_arr = array();

		foreach($items as $item){
			array_push($items_arr,$this->getPoItem($item));
		}

		return $items_arr;
	}

	public function getOpenItems()
	{

		$subdel = PurchaseOrderDelivery::select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
			as totalDelivered'),'poidel_item_id')->groupBy('poidel_item_id');

		$subjo = JobOrder::select(DB::raw('sum(jo_quantity) as totalJo'),'jo_po_item_id')
			->groupBy('jo_po_item_id');

		$q = PurchaseOrderItems::query();
		$q->from('cposms_purchaseorderitem')
		->leftJoinSub($subdel,'delivery',function ($join){
			$join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');				
		})->leftJoinSub($subjo,'jo',function ($join){
			$join->on('cposms_purchaseorderitem.id','=','jo.jo_po_item_id');				
		});

		$q->whereRaw('poi_quantity > IFNULL(totalDelivered,0)');
		$q->whereRaw('poi_quantity > IFNULL(totalJo,0)');
		$itemResult = $q->orderBy('id','desc')->paginate();

		$openItems = $this->getPoItems($itemResult);

		return response()->json([
			'openItems' => $openItems
		]);

	}

}
