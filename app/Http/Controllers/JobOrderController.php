<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\JobOrderExport;
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

	//export
	public function exportJobOrder()
	{
		return Excel::download(new JobOrderExport, 'job_order.xlsx');
	}

	public function cleanString($string){
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

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

		$q->whereHas('po', function($q){
			$q->where('isEndorsed',1);
		});

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
			'jo_remarks' => $this->cleanString($val['remarks']),
			'jo_others' => $this->cleanString($val['others']),
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
		$sort = strtolower(request()->sort);
		$showRecord = strtolower(request()->showRecord);
		$subProd = JobOrderProduced::select(Db::raw('sum(jop_quantity) as totalProduced'),
			'jop_jo_id')->groupBy('jop_jo_id');


		$q = JobOrder::query();

		$q->has('poitems.po');

		if(request()->has('search')){

			$search = "%".strtolower(request()->search)."%";

			$q->whereHas('poitems.po', function($q) use ($search){
				$q->where('po_ponum','LIKE', $search);
			})->orWhereHas('poitems', function($q) use ($search){
				$q->where('poi_itemdescription','LIKE',$search)
				->orWhere('poi_partnum','LIKE',$search);
			})->orWhere('jo_joborder','LIKE',$search);

		}

		if($showRecord == 'open' || $showRecord == 'served'){

			$q->from('pjoms_joborder')
			->leftJoinSub($subProd,'produced',function ($join){
				$join->on('pjoms_joborder.id','=','produced.jop_jo_id');				
			});

			if($showRecord == 'open')
				$q->whereRaw('jo_quantity > IFNULL(totalProduced,0)');
			else 
				$q->whereRaw('jo_quantity <= IFNULL(totalProduced,0)');

		}


		if($sort == 'desc'){
			$q->orderBy('id','DESC');
		}else if($sort == 'asc'){
			$q->orderBy('id','ASC');
		}else if($sort == 'di-desc'){
			$q->orderBy('jo_dateissued','DESC');
		}else if($sort == 'di-asc'){
			$q->orderBy('jo_dateissued','ASC');
		}else if($sort == 'jo-desc'){
			$q->orderBy('jo_joborder','DESC');
		}else if($sort == 'jo-asc'){
			$q->orderBy('jo_joborder','ASC');
		}


		$joResult = $q->paginate($pageSize);
		
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

		$this->logJoCreateDelete("Added",$item->po->po_ponum,$item->poi_itemdescription,
			$request->jo_num,$request->quantity,null);

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

		if($jo->isDirty()){
			$origVal = $jo->getOriginal();
			$dirtyVal = $jo->getDirty();
			if(array_key_exists('jo_forwardToWarehouse',$jo->getDirty())){

				$origVal['jo_forwardToWarehouse'] = str_replace([1,0],['Yes','No'],$origVal['jo_forwardToWarehouse']);
				$dirtyVal['jo_forwardToWarehouse'] = str_replace([1,0],['Yes','No'],$dirtyVal['jo_forwardToWarehouse']);
			}

			$this->logJoEdit($dirtyVal,$origVal,$request->jo_num);

		}

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
		$jo_num = $jo->jo_joborder;
		$item_id = $jo->jo_po_item_id;
		$jo->delete();
		$item = PurchaseOrderItems::findOrFail($item_id);
		$get_jos = $item->jo()->get();
		$updatedJos = $this->getJos($get_jos);

		$this->logJoCreateDelete("Deleted",
			$item->po->po_ponum,
			$item->poi_itemdescription,
			$jo_num,
			$item->poi_quantity,
			request()->remarks);

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

		$this->logJoProducedCreateDelete("Added",$jo->jo_joborder,
			$request->quantity,$remaining,$request->date,$request->remarks);

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
		$jo = JobOrder::findOrFail($produced->jop_jo_id);
		$prodQty = $produced->jop_quantity; //get qty
		$prodDate = $produced->jop_date; //get date

		$produced->delete();

		$updatedJo = $this->getJo($jo);
		$remaining = $jo->jo_quantity - $jo->produced()->sum('jop_quantity');

		$this->logJoProducedCreateDelete("Deleted",$jo->jo_joborder,
			$prodQty,$remaining,$prodDate,null);

		return response()->json(
			[
				'message' => "Record deleted",
				'updatedJo' => $updatedJo
			]);

	}

	public function closeJobOrder($id){

		$jo = JobOrder::findOrFail($id);
		$joTotalProduced = $jo->produced()->sum('jop_quantity');
		$remaining = $jo->jo_quantity - $joTotalProduced;

		if($remaining < 1)
			return response()->json(['errors' => ['Job order already served']],422);

		$produced = $jo->produced()->create([
			'jop_quantity' => $remaining,
			'jop_date' => Carbon::now()->format('Y-m-d'),
			'jop_remarks' => 'Closed by system'
		]);

		$jo->refresh();
		$updatedJo = $this->getJo($jo);

		return response()->json(
			[
				'message' => 'Job order closed',
				'updatedJo' => $updatedJo
			]);

	}

}
