<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\JobOrderExport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LogsController;

use App\PurchaseOrder;
use App\Customers;
use App\PurchaseOrderItems;
use App\PurchaseOrderDelivery;
use App\JobOrder;
use App\JobOrderProduced;
use App\JobOrderSeries;
use App\Inventory;
use App\PurchaseOrderSupplierItems;

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

  public function getCustomers(){
    $customer = Customers::select('companyname')->get()
      ->pluck('companyname')->toArray();
    return $customer;
  }

	public function getPoItem($item)
	{

		$qtyWithoutJo = $item->poi_quantity - $item->totalJo;
		return array(
			'item_id' => $item->id,
			'customer' => $item->po->customer->companyname,
			'po_num' => $item->po->po_ponum,
			'code' => $item->poi_code,
			'itemdesc' => $item->poi_itemdescription,
			'quantity' => $item->poi_quantity,
			'currency' => $item->po->po_currency,
			'unitprice' => $item->poi_unitprice,
			'deliverydate' => $item->poi_deliverydate,
			'kpi' => $item->poi_kpi,
      'qtyWithoutJo' => $qtyWithoutJo,
			'totalJoQty' => $item->jo->sum('jo_quantity'),
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
    $showItems = strtolower(request()->showItems);
    //SUB QUERIES
		$delivery = PurchaseOrderDelivery::select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
		as totalDelivered'),'poidel_item_id')->groupBy('poidel_item_id');

		$jobOrder = JobOrder::select(DB::raw('sum(jo_quantity) as totalJoQty'),'jo_po_item_id')
			->groupBy('jo_po_item_id');

		$purchaseOrder = PurchaseOrder::select('id', 'po_customer_id','po_ponum', 'po_currency','isEndorsed')
			->groupBy('id');

		$customer = Customers::select('id','companyname')
			->groupBy('id');

		$q = PurchaseOrderItems::has('po');
    // JOIN
		$q->leftJoinSub($delivery,'delivery',function ($join){
				$join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');				
			})
			->leftJoinSub($jobOrder,'jo',function ($join){
				$join->on('cposms_purchaseorderitem.id','=','jo.jo_po_item_id');				
			})
			->leftJoinSub($purchaseOrder,'po',function ($join){
				$join->on('cposms_purchaseorderitem.poi_po_id','=','po.id');				
			})
			->leftJoinSub($customer,'customer',function ($join){
				$join->on('customer.id','=','po.po_customer_id');				
			});
    // SELECT
    $q->select(
      'cposms_purchaseorderitem.id as item_id',
      'companyname as customer',
      'po_ponum as po_num',
      'poi_code as code',
      'poi_itemdescription as itemdesc',
      'poi_quantity as quantity',
      'po_currency as currency',
      'poi_unitprice as unitprice',
      'poi_deliverydate as deliverydate',
      'poi_kpi as kpi',
      Db::raw('CAST(IFNULL(totalJoQty,0) as int) as totalJoQty'),
      Db::raw('CAST(poi_quantity - IFNULL(totalJoQty,0) as int) as qtyWithoutJo')
    );

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

    //VIEWING
    if($showItems == 'pending'){
      $q->whereRaw('poi_quantity > IFNULL(totalJoQty,0)');
    }

    $q->whereRaw('poi_quantity > IFNULL(totalDelivered,0)')
      ->whereRaw('isEndorsed = 1');

    if(request()->has('customer')) {
      $q->whereRaw('companyname = ?',array(request()->customer));
    }

    //SORT
    if (request()->has('sort')) {
      $sort = strtolower(request()->sort);

      if($sort == 'del_asc') 
        $q->orderBy('poi_deliverydate', 'ASC');
      else if($sort == 'del_desc') 
        $q->orderBy('poi_deliverydate', 'DESC');
      else if($sort == 'oldest')
        $q->oldest();
      else 
        $q->latest();
    }
    //return
		$result = $q->paginate(1000);
		return response()->json([
			'openItems' => $result->items(),
			'openItemsLength' => $result->total(),
      'customers' => $this->getCustomers(),
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
			'poNum' => $po->po_ponum,
			'customer' => $po->customer->companyname,
			'code' => $item->poi_code,
			'itemDesc' => $item->poi_itemdescription,
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
			'hasPr' => $jo->pr()->count() > 0 
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
      'pjoms_joborder.id',
      'item.id as item_id',
      'po_ponum as poNum',
      'jo_joborder as jo_num',
      'companyname as customer',
      'poi_code as code',
      'poi_itemdescription as itemDesc',
      'jo_dateissued as date_issued',
      'jo_dateneeded as date_needed',
      'jo_quantity as quantity',
      'jo_remarks as remarks',
      'jo_others as others',
      Db::raw('IF(jo_quantity > IFNULL(producedQty,0),"OPEN","SERVED" ) as status'),
      Db::raw('IFNULL(producedQty,0) as producedQty'),
      'jo_forwardToWarehouse as forwardToWarehouse',
      DB::raw('(poi_quantity - totalJobOrderQty) + jo_quantity as qtyWithoutJo'),
      DB::raw('IF(IFNULL(prCount,0) > 0,true,false) as hasPr')
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

		$joResult = $q->paginate(1000);
		return response()->json(
			[
				'joList' => $joResult->items(),
				'joListLength' => $joResult->total(),
        'customers' => $this->getCustomers(),
			]);

	}

	public function fetchJoSeries()
	{
		$series = JobOrderSeries::first();
		$number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
		$joseries = $series->series_prefix.date('y'). "-".$number;
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
		$remainingQty = $remaining - $request->quantity;
		$this->logJoCreateDelete("Added",$item->po->po_ponum,$item->poi_itemdescription,
			$request->jo_num,$request->quantity,null);

    $item->refresh();
    $newItem = $this->getPoItem($item);
		return response()->json(
			[
				'newItem' => $newItem,
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
    if($jo->pr) {
      return response()->json(['errors' => ['Job order is not deletable!']]);
    }

		$jo_num = $jo->jo_joborder;
		$item_id = $jo->jo_po_item_id;
    $jo->produced->delete();
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

		$dateNow = Carbon::now()->format('Y-m-d');
		$remarks = 'Closed by system';
		$produced = $jo->produced()->create([
			'jop_quantity' => $remaining,
			'jop_date' => $dateNow,
			'jop_remarks' => $remarks
		]);

		$this->logJoProducedCreateDelete("Added",$jo->jo_joborder,
			$remaining,$remaining,$dateNow,$remarks);

		$jo->refresh();
		$updatedJo = $this->getJo($jo);

		return response()->json(
			[
				'message' => 'Job order closed',
				'updatedJo' => $updatedJo
			]);

	}

	public function printJobOrder()
	{
		if(!request()->has('jos')){
			return response()->json(
				[
					'error' => ['No job order ids']
				],422);
		}

		$ids = request()->jos;
		$joIds = explode("-",$ids);
		$joborders = $this->getJos(JobOrder::whereIn('id',$joIds)->get());

		$pdf = PDF::loadView('pjoms.printJobOrder',compact('joborders'))->setPaper('a4','portrait');
		return $pdf->download('job_orders.pdf');

	}

	public function getItemDetails($id)
	{

    $deliverySub = Db::table('psms_supplierinvoice')
      ->select('ssi_poitem_id as id',Db::raw('IFNULL(sum(ssi_receivedquantity), 0) as totalDelivered'))
      ->groupBy('ssi_poitem_id');

		$item = PurchaseOrderItems::findOrFail($id);
    $code = $item->poi_code;
    $orderedItems = PurchaseOrderSupplierItems::whereHas('spo.prprice.pr.jo.poitems', function($q) use ($code){
        return $q->where('poi_code', $code);
      })
      ->leftJoinSub($deliverySub, 'delivery', function($join){
        return $join->on('psms_spurchaseorderitems.id','=','delivery.id');
      })
      ->select(
        'psms_spurchaseorderitems.id',
        'spoi_code as code',
        'spoi_mspecs as mspecs',
        Db::raw('CAST(totalDelivered as int) as totalDelivered'),
        Db::raw('CAST(spoi_quantity - totalDelivered as int) as pendingDelivery')
      )
      ->get();
    $inventory = Inventory::select(
        'id',
        'i_mspecs as mspecs',
        'i_partnumber as partnumber',
        'i_code as code',
        'i_quantity as quantity',
        'i_min as min',
        'i_max as max'
      )->get();

		$itemDeliveryDetails = array();
		$jobOrderDetails = array();
		//loop through delivery of the item
		foreach($item->delivery()->latest()->get() as $delivery)
		{

			array_push($itemDeliveryDetails,
				array(
					'id' => $delivery->id.uniqid(),
					'quantity' => $delivery->poidel_quantity,
					'deliverydate' => $delivery->poidel_deliverydate,
				)
			);

		}
		//loop through jo of the item
		foreach($item->jo as $jo)
		{
			$producedQty = intval($jo->produced()->sum('jop_quantity'));
			$status = $jo->jo_quantity > $producedQty ? 'OPEN' : 'SERVED';
			array_push($jobOrderDetails,
				array(
					'id' => $jo->id.uniqid(),
					'jobOrder' => $jo->jo_joborder,
					'date_issued' => $jo->jo_dateissued,
					'date_needed' => $jo->jo_dateneeded,
					'quantity' => $jo->jo_quantity,
					'producedQty' => $producedQty,
					'status' => $status,
					'forwardToWarehouse' => $jo->jo_forwardToWarehouse ? 'YES' : 'NO',
				)
			);

		}

    $series = JobOrderSeries::first();
    $number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
    $joSeries = $series->series_prefix.date('y'). "-".$number;

		return response()->json(
			[
        'orderedItems' => $orderedItems,
				'itemDeliveryDetails' => $itemDeliveryDetails,
				'jobOrderDetails' => $jobOrderDetails,
        'inventory' => $inventory,
        'joSeries' => $joSeries,
			]);

	}

}
