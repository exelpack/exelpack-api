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
use App\JobOrderSeries;

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
			'item_id' => $item->id,
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
		$sortDate = request()->sortDate;
		$showItems = strtolower(request()->showItems);
		$pageSize = request()->pageSize;

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

		$q->has('po');

		//search filter
		if(request()->has('search')){
			$search = '%'.request()->search.'%';

			$q->whereHas('po', function($q) use ($search){
				$q->where('po_ponum','LIKE', $search);
			})
			->orWhere('poi_itemdescription','LIKE', $search)
			->orWhere('poi_code','LIKE', $search)
			->orWhere('poi_partnum','LIKE', $search);
		}

		$q->whereRaw('poi_quantity > IFNULL(totalDelivered,0)');

		if($showItems == 'pending'){
			$q->whereRaw('poi_quantity > IFNULL(totalJo,0)');
		}

		$itemResult = $q->orderBy('poi_deliverydate',$sortDate)->paginate($pageSize);

		$openItems = $this->getPoItems($itemResult);
		$joSeries = $this->fetchJoSeries();
		return response()->json([
			'openItems' => $openItems,
			'openItemsLength' => $itemResult->total(),
			'joSeries' => $joSeries
		]);

	}

	public function joArray($val)
	{

		return array(
			'jo_joborder' => $val['jo_num'],
			'jo_dateissued' => $val['date_issued'],
			'jo_dateneeded' => $val['date_needed'],
			'jo_quantity' => $val['quantity'],
			'jo_remarks' => $val['remarks'],
			'jo_others' => $val['others'],
			'jo_forwardToWarehouse' => $val['forwardToWarehouse'],
		);

	}

	public function getJo($jo)
	{
		$po = $jo->poitems->po;
		$item = $jo->poitems;
		$totalJo = $jo->poitems->jo()->sum('jo_quantity');
		$remaining = ($item->poi_quantity - $totalJo) + $jo->jo_quantity;

		$producedQty = intval($jo->produced()->sum('jop_quantity'));
		$status = $jo->jo_quantity > $producedQty ? 'OPEN' : 'SERVED';
		return array(
			'id' => $jo->id,
			'item_id' => $item->id,
			'po_num' => $po->po_ponum,
			'customer' => $po->customer->companyname,
			'code' => $item->poi_code,
			'part_num' => $item->poi_partnum,
			'item_desc' => $item->poi_itemdescription,
			'jo_num' => $jo->jo_joborder,
			'date_issued' => $jo->jo_dateissued,
			'date_needed' => $jo->jo_dateneeded,
			'quantity' => $jo->jo_quantity,
			'remarks' => $jo->jo_remarks,
			'others' => $jo->jo_others,
			'status' => $status,
			'producedQty' => $producedQty,
			'forwardToWarehouse' => $jo->jo_forwardToWarehouse,
			'qtyWithoutJo' => $remaining,
			'hasPr' => false
		);
	}

	public function getJos($jos)
	{
		$jo_arr = array();

		foreach($jos as $jo)
		{
			array_push($jo_arr,$this->getJo($jo));
		}

		return $jo_arr;
	}

	public function fetchJo()
	{

		$pageSize = request()->pageSize;
		$q = JobOrder::query();

		$q->has('poitems.po');
		$joResult = $q->orderBy('id','DESC')->paginate($pageSize);
		
		$joList = $this->getJos($joResult);

		return response()->json(
			[
				'joList' => $joList,
				'joListLength' => $joResult->total()
			]);

	}

	public function fetchJoSeries()
	{
		$series = JobOrderSeries::first();

		$number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
		$joseries = $series->series_prefix . "-".$number;
		return $joseries;
	}

	public function createJo(Request $request)
	{
		$item = PurchaseOrderItems::findOrFail($request->item_id);
		$totalJo = $item->jo()->sum('jo_quantity');
		$remaining = $item->poi_quantity - $totalJo;
		$validator = Validator::make($request->all(),
			[
				'jo_num' => 'unique:pjoms_joborder,jo_joborder|required|max:60',
				'date_issued' => 'required|before_or_equal:'.date('Y-m-d'),
				'date_needed' => 'required|after_or_equal:'.$request->date_issued,
				'quantity' => 'integer|required|min:1|max:'.$remaining,
				'remarks' => 'string|nullable|max:150',
				'others' => 'string|nullable|max:150',
				'forwardToWarehouse' => 'boolean|required',
				'useSeries' => 'boolean|required',
			],[],['jo_num' => 'job order number']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$jo = $item->jo()->create($this->joArray($request->all()));

		if($request->useSeries){
			JobOrderSeries::first()->update(['series_number' => DB::raw('series_number + 1')]);
		}
		$joSeries = $this->fetchJoSeries();
		$remainingQty = $remaining - $request->quantity;

		return response()->json(
			[
				'joSeries' => $joSeries,
				'remainingQty' => $remainingQty,
				'message' => 'Record added',
			]);

	}
	
	public function updateJo(Request $request,$id)
	{
		$item = PurchaseOrderItems::findOrFail($request->item_id);
		$totalJo = $item->jo()->sum('jo_quantity');
		
		$joInfo = JobOrder::findOrFail($id);
		$remaining = ($item->poi_quantity - $totalJo) + $joInfo->jo_quantity;

		$validator = Validator::make($request->all(),
			[
				'jo_num' => 'required|max:60|unique:pjoms_joborder,jo_joborder,'.$id,
				'date_issued' => 'required|before_or_equal:'.date('Y-m-d'),
				'date_needed' => 'required|after_or_equal:'.$request->date_issued,
				'quantity' => 'integer|required|min:1|max:'.$remaining,
				'remarks' => 'string|nullable|max:150',
				'others' => 'string|nullable|max:150',
				'forwardToWarehouse' => 'boolean|required',
			],[],['jo_num' => 'job order number']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$jo = $joInfo->fill($this->joArray($request->all()));
		$jo->save();

		$get_jos = PurchaseOrderItems::findOrFail($request->item_id)->jo()->get(); //to update all remaining jos available qty
		$updatedJos = $this->getJos($get_jos);

		return response()->json(
			[
				'updatedJos' => $updatedJos
			]);

	}

	public function deleteJo($id)
	{

		$jo = JobOrder::findOrFail($id);
		$item_id = $jo->jo_po_item_id;
		$jo->delete();
		$get_jos = PurchaseOrderItems::findOrFail($item_id)->jo()->get();
		$updatedJos = $this->getJos($get_jos);

		return response()->json(
			[
				'message' => 'Record deleted',
				'updatedJos' => $updatedJos
			]);

	}
	//jo produced
	public function getJoProducedQty($id)
	{

		$produced = JobOrder::findOrFail($id)->produced()->get();
		$produced_arr = array();

		foreach($produced as $prod)
		{

			array_push($produced_arr,
				array(
					'id' => $prod->id,
					'quantity' => $prod->jop_quantity,
					'date' => $prod->jop_date,
					'remarks' => $prod->jop_remarks,
				)
			);

		}

		return response()->json(
			[
				'joProduced' => $produced_arr
			]);

	}

	public function addJoProduced(Request $request)	
	{

		$jo = JobOrder::findOrFail($request->id);
		$remaining = $jo->jo_quantity - $jo->produced()->sum('jop_quantity');

		$validator = Validator::make($request->all(),
			[
				'date' => 'required|before_or_equal:'.date('Y-m-d'),
				'quantity' => 'integer|required|min:1|max:'.$remaining,
				'remarks' => 'string|nullable|max:150',
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$produced = $jo->produced()->create(
			[
				'jop_quantity' => $request->quantity,
				'jop_date' => $request->date,
				'jop_remarks' => $request->remarks
			]);

		$newProduced = array(
			'id' => $produced->id,
			'quantity' => $produced->jop_quantity,
			'date' => $produced->jop_date,
			'remarks' => $produced->jop_remarks,
		);
		
		$updatedJo = $this->getJo($jo);
		return response()->json(
			[
				'message' => "Record added",
				'newProduced' => $newProduced,
				'updatedJo' => $updatedJo
			]);

	}

	public function deleteJoProduced($id){

		$produced = JobOrderProduced::findOrFail($id);
		$jo_id = $produced->jop_jo_id;

		$produced->delete();

		$jo = JobOrder::findOrFail($jo_id);
		$updatedJo = $this->getJo($jo);

		return response()->json(
			[
				'message' => "Record deleted",
				'updatedJo' => $updatedJo
			]);

	}

}