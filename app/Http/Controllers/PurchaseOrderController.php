<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\PurchaseOrderDelivery;
use App\PurchaseOrderSchedule;
use App\Masterlist;
use App\Customers;
use App\UserLogs;

use DB;
use Excel;
use Crypt;
use PDF;
use Carbon\Carbon;

use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseOrderItemExport;
use App\Exports\PoDeliveryScheduleExport;
use App\Exports\PoItemDeliveredExport;

class PurchaseOrderController extends LogsController 
{

	public function cleanString($string){
		$string = str_replace(","," ",$string);//replace comma with space
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	// exports
	public function exportPoCsv()
	{
		return Excel::download(new PurchaseOrderExport, 'purchaseorders.xlsx');
	}

	public function exportPoItemsCsv()
	{
		return Excel::download(new PurchaseOrderItemExport, 'purchaseorderitem.xlsx');
	}

	public function exportPoDailySchedule()
	{
		return Excel::download(new PoDeliveryScheduleExport, 'podeliveryschedule.xlsx');
	}

	public function exportPoDelivered()
	{
		return Excel::download(new PoItemDeliveredExport, 'purchaseorderitemdelivered.xlsx');
	}

	public function exportPoDailyScheduleToPDF()
	{
	
		$date = request()->date;
		$dailyScheds = PurchaseOrderSchedule::whereDate('pods_scheduledate',$date)
			->has('item')
			->get();
		$schedules = $this->getSchedules($dailyScheds);
		$data = [
			'schedules' => $schedules,
			'date' => $date
		];

		$pdf = PDF::loadView('cposms.itemDailySChedule', $data)->setPaper('a4','landscape');
		return $pdf->download('Schedule_for_'.$date.'.pdf');

	}

	public function test()
	{
		$date = request()->date;
		$dailyScheds = PurchaseOrderSchedule::whereDate('pods_scheduledate',$date)
			->has('item')
			->get();
		$schedules = $this->getSchedules($dailyScheds);
		$data = [
			'schedules' => $schedules,
			'date' => $date
		];
		// return view('cposms.itemDailySChedule', $data);
		$pdf = PDF::loadView('cposms.itemDailySChedule', $data)->setPaper('a4','landscape');
		return $pdf->download('Schedule_for_'.$date.'.pdf');
	}
	// end exports

	public function getOptionsPOSelect()
	{
		$customers = Customers::select('id','companyname')->orderBy('companyname','ASC')->get();
		$itemSelectionList	= Masterlist::select('id','m_projectname as itemdesc',
			'm_partnumber as partnum','m_code as code','m_unit as unit','m_unitprice as unitprice')->get();

		return response()->json(
			[
				'customers' => $customers,
				'itemSelectionList' => $itemSelectionList
			]);

	}
	// PO
	public function itemArray($item)
	{
		return [
			'poi_code' => $item['code'],
			'poi_partnum' => $item['partnum'],
			'poi_itemdescription' => $item['itemdesc'],
			'poi_quantity' => $item['quantity'],
			'poi_unit' => $item['unit'],
			'poi_unitprice' => $item['unitprice'],
			'poi_deliverydate' => $item['deliverydate'],
			'poi_kpi' => $item['kpi'],
			'poi_others' => $this->cleanString($item['others']),
			'poi_remarks' => $this->cleanString($item['remarks']),
		];
	}

	public function getPo($po)
	{
		$items = $this->getItems($po->poitems);
		$hasJo = $po->poitems()->has('jo')->count() > 0 ? true : false;
		$totalQuantity = $po->getTotalItemQuantity->totalQuantity;
		$totalDelivered = intval($po->getTotalDeliveryQuantity->totalDelivered);
		$status = $po->poitems()->count() > 0 ? $totalDelivered >= $totalQuantity 
		? 'SERVED' : 'OPEN' : 'NO ITEM';
		return array(
			'id' => $po->id,
			'po_num' => $po->po_ponum,
			'customer_label' => $po->customer->companyname,
			'customer' => $po->po_customer_id,
			'date' => $po->po_date,
			'currency' => $po->po_currency,
			'totalItems'=> $po->poitems()->count(),
			'totalQuantity'=> $totalQuantity,
			'totalDelivered'=> $totalDelivered,
			'status' => $status,
			'hasJo' => $hasJo,
			'items' => $items,
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

		$diff =	Carbon::now()->diff($item->poi_deliverydate)->days;
		$hasWarning = $diff < 3 && $status == 'OPEN' ? true : false; 

		//restrict
		$isNotEditable = $status === 'SERVED' ? true : false; //if served. then not editable anymore
		$withJo = $item->jo()->count() > 0 ? true : false; // if with jo
		$qtyDisabled = $isNotEditable || $delivered !== 0 || $withJo;// disabled editing of quantity
		$itemRemovable = !$withJo && !$isNotEditable && $delivered === 0;//if no jo andnot served and doesnt have delivered

		$hasDelivery = $item->delivery()->count() > 0 ? true : false;
		$hasSchedule = $item->schedule()->count() > 0 ? true : false;

		return array(
			'id' => $item->id,
			'customer' => $item->po->customer->companyname,
			'customer_id' => $item->po->po_customer_id,
			'status' => $status,
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
			'withJo' => $withJo,
			'isNotEditable' => $isNotEditable,
			'qtyDisabled' => $qtyDisabled,
			'itemRemovable' => $itemRemovable,
			'hasWarning' => $hasWarning,
			'hasDelivery' => $hasDelivery,
			'hasSchedule' => $hasSchedule,
		);

	}

	public function poIndex()
	{
		$pageSize = request()->pageSize;

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
		$po_result = $q->paginate($pageSize);	
		$po = $this->getPos($po_result);
		
		return response()->json(
			[
				'po' => $po,
				'poLength' => $po_result->total(),
			]);
	}

	public function poItemsIndex()
	{
		$pageSize = request()->pageSize;
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
		$poItems_result = $q->paginate($pageSize);
		$poItems = $this->getItems($poItems_result);

		return response()->json(
			[
				'poItems' => $poItems,
				'poItemsLength' => $poItems_result->total(),
			]);
	}

	public function createPurchaseOrder(Request $request){

		$cleanPO = $this->cleanString($request->po_num);

		$validator = Validator::make($request->all(),
			[
				'po_num' => 'unique:cposms_purchaseorder,po_ponum|required|max:100',
				'customer' => 'required',
				'currency' => 'required',
				'items' => 'array|min:1',
			],[],['po_num' => 'purchase order number']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$po = new PurchaseOrder();
		$po->fill(
			[
				'po_customer_id' => $request->customer,
				'po_currency' => $request->currency,
				'po_date' => $request->date,
				'po_ponum' => $cleanPO,
			]);

		$po->save();

		$this->logPoCreate($po,count($request->items)); //create log for po

		foreach($request->items as $row){

			$po->poitems()->create($this->itemArray($row));

		}

		$newItem = $this->getPo($po);
		return response()->json([
			'newItem' => $newItem,
			'message' => 'Record added'
		]);

	}

	public function editPurchaseOrder(Request $request){

		$cleanPO = $this->cleanString($request->po_num);

		$validator = Validator::make($request->all(),
			[
				'po_num' => 'unique:cposms_purchaseorder,po_ponum,'.$request->id.'|required|max:100',
				'customer' => 'required',
				'currency' => 'required',
				'items' => 'array|min:1',
			],[],['po_num' => 'purchase order number']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$po = PurchaseOrder::find($request->id);
		$po->po_customer_id = $request->customer;
		$po->po_currency = $request->currency;
		$po->po_date = $request->date;
		$po->po_ponum = $cleanPO;

		if($po->isDirty()){
			$this->logPoEdit($po->getDirty(),$po->getOriginal());
			$po->save();
		}

		$poitems_count = $po->poitems()->count();// get original po item count
		$poitems_ids = $po->poitems()->pluck('id')->toArray(); //get original po item id
		$items_ids = array_column($request->items,'id'); //get request items id

		//deletion of item
		foreach($po->poitems as $item){
			if(!in_array($item->id,$items_ids)){
				$po->poitems()->find($item['id'])->delete();
			}
		}

		foreach($request->items as $item){ //adding and editing item
			if(isset($item['id'])){ //check if item exists on po alr then update
				$po->poitems()->find($item['id'])->update($this->itemArray($item));
			}else{
				//if item doesnt exist on po then add.
				$po->poitems()->create($this->itemArray($item));
			}
		}
		$po->refresh();
		$newItem = $this->getPo($po);
		return response()->json([
			'newItem' => $newItem,
			'message' => 'Record updated'
		]);
	}

	public function cancelPo($id)
	{
		$remarks = request()->remarks;
		$po = PurchaseOrder::find($id);
		$po->update(['po_cancellationRemarks' => $remarks]);
		$po->delete();

		return response()->json([
			'message' => 'Record cancelled'
		]);
	}
	// delivery functions

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
			'id' => $delivery->id,
			'customer' => $delivery->item->po->customer->companyname,
			'quantity' => $delivery->poidel_quantity,
			'underrun' => $delivery->poidel_underrun_qty,
			'date' => $delivery->poidel_deliverydate,
			'invoice' => $delivery->poidel_invoice,
			'dr' => $delivery->poidel_dr,
			'remarks' => $delivery->poidel_remarks,
			'po_num' => $delivery->item->po->po_ponum,
			'item_desc' => $delivery->item->poi_itemdescription,
		);

	}

	public function getItemDeliveryStats($item) //get total qty, delivered, and remainig
	{
		$totalDelivered = $item->delivery()->sum(DB::raw('poidel_quantity + poidel_underrun_qty'));
		$itemQuantity = $item->poi_quantity;
		$remainingQty =  $itemQuantity - $totalDelivered;

		return [
			'itemQuantity' => $itemQuantity,
			'itemDelivered' => $totalDelivered,
			'itemRemaining' => $remainingQty,
		];
	}

	public function itemDeliveryArray($delivery){

		return [
			'poidel_quantity' => $delivery->quantity ? $delivery->quantity : 0,
			'poidel_underrun_qty' => $delivery->underrun ? $delivery->underrun : 0,
			'poidel_deliverydate' => $delivery->date,
			'poidel_invoice' => $delivery->invoice,
			'poidel_dr' => $delivery->dr,
			'poidel_remarks' => $delivery->remarks,
		];

	}

	public function fetchItemDelivery($id) //fetch delivered for the item
	{

		$item = PurchaseOrderItems::find($id);
		$stats = $this->getItemDeliveryStats($item);
		$itemDeliveries = $this->getDeliveries($item->delivery()->orderBy('id','DESC')->get());

		return response()->json(array_merge($stats,
			['item_id' => $id,'itemDeliveries' => $itemDeliveries]));

	}

	public function fetchDeliveries()
	{
		$q = PurchaseOrderDelivery::query();
		$pageSize = request()->pageSize;

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
		$deliveries_result = $q->paginate($pageSize);

		$deliveredItems = $this->getDeliveries($deliveries_result);
		return response()->json(
			[
				'deliveredItems' => $deliveredItems,
				'deliveredLength' => $deliveries_result->total(),
			]);

	}

	public function addDelivery(Request $request)
	{

		$item = PurchaseOrderItems::find($request->item_id);
		$stats = $this->getItemDeliveryStats($item);

		$validator = Validator::make($request->all(),
			[
				'totalQty' => 'integer|min:1|max:'.$stats['itemRemaining'],
				'quantity' => 'integer|nullable|required_if:underrun,null,0',
				'underrun' => 'integer|nullable|required_if:quantity,null,0',
				'date' => 'required|before_or_equal:'.date('Y-m-d'),
				'dr' => 'string|max:70|nullable',
				'invoice' => 'string|max:70|nullable',
				'remarks' => 'string|max:150|nullable',
			],[],['totalQty' => 'Total delivered quantity & underrun']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item->delivery()->create($this->itemDeliveryArray($request));//add
		$item->refresh(); // refresh item content
		$newDelivery = $this->getDelivery($item->delivery()->latest('created_at')->first()); //get the latest added record
		$newStats = $this->getItemDeliveryStats($item);
		$updatedItem = $this->getItem($item);

		return response()->json(array_merge($newStats,
			[
				'newDelivery' => $newDelivery,
				'updatedItem' => $updatedItem,
				'message' => 'Record added'
			]));

	}

	public function editDelivery(Request $request,$id)
	{

		$item = PurchaseOrderItems::find($request->item_id);
		$stats = $this->getItemDeliveryStats($item);
		$delivery = PurchaseOrderDelivery::find($id);
		$remaining = $delivery->poidel_quantity + $delivery->poidel_underrun_qty + $stats['itemRemaining'];

		$validator = Validator::make($request->all(),
			[
				'totalQty' => 'integer|min:1|max:'.$remaining,
				'quantity' => 'integer|nullable|required_if:underrun,null|required_if:underrun,0',
				'underrun' => 'integer|nullable|required_if:quantity,null|required_if:quantity,0',
				'date' => 'required|before_or_equal:'.date('Y-m-d'),
				'dr' => 'string|max:70|nullable',
				'invoice' => 'string|max:70|nullable',
				'remarks' => 'string|max:150|nullable',
			],[],['totalQty' => 'Total delivered quantity & underrun']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$delivery->update($this->itemDeliveryArray($request));
		$delivery->refresh();
		$item->refresh(); // refresh item content
		$updateDelivery = $this->getDelivery($delivery); //get the edited record
		$newStats = $this->getItemDeliveryStats($item);
		$updatedItem = $this->getItem($item);

		return response()->json(array_merge($newStats,
			[
				'updatedDelivery' => $updateDelivery,
				'updatedItem' => $updatedItem,
				'message' => 'Record updated'
			]));

	}

	public function deleteDelivery($id)
	{

		$delivery = PurchaseOrderDelivery::find($id);
		$item_id = $delivery->poidel_item_id;

		$delivery->delete();
		$item = PurchaseOrderItems::find($item_id);
		$newStats = $this->getItemDeliveryStats($item);
		$updatedItem = $this->getItem($item);

		return response()->json(array_merge($newStats,
			[
				'id' => intval($id),
				'updatedItem' => $updatedItem,
				'message' => 'Record deleted'
			]
		));

	}

	//schedule
	public function getSchedule($sched)
	{

		$hasPrivelege = auth()->user()->id == $sched->pods_user_id ? true : false;

		return [
			'id' => $sched->id,
			'hasPrivelege' => $hasPrivelege,
			'customer' => $sched->item->po->customer->companyname,
			'po' => $sched->item->po->po_ponum,
			'itemdesc' => $sched->item->poi_itemdescription,
			'date' => $sched->pods_scheduledate,
			'quantity' => $sched->pods_quantity,
			'remaining' => $sched->pods_remaining,
			'remarks' => $sched->pods_remarks,
			'commited_qty' => $sched->pods_commit_qty,
			'prod_remarks' => $sched->pods_prod_remarks,
			'others' => $sched->item->poi_others,
			'jo' => implode(",",$sched->item->jo->pluck('jo_joborder')->toArray())
		];

	}

	public function getSchedules($scheds)
	{

		$sched_arr = [];
		foreach($scheds as $sched){
			array_push($sched_arr,$this->getSchedule($sched));
		}

		return $sched_arr;

	}

	public function getDailySchedules($date)
	{

		$dailyScheds = PurchaseOrderSchedule::whereDate('pods_scheduledate',$date)
		->has('item')
		->get();
		$scheds = $this->getSchedules($dailyScheds);

		return response()->json(
			[
				'dailySchedule' => $scheds
			]);

	}

	public function getOpenItems()
	{

		$q = PurchaseOrderItems::query();
		$q->has('po'); //fetch item with po only
		$date = request()->date;

		$sub = PurchaseOrderDelivery::select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
			as totalDelivered'),'poidel_item_id')->groupBy('poidel_item_id');

		$q->from('cposms_purchaseorderitem')
		->leftJoinSub($sub,'delivery',function ($join){
			$join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');				
		});

		$q->whereDoesntHave('schedule', function($q) use ($date){
			$q->where('pods_scheduledate', $date);
		});

		$q->whereRaw('poi_quantity > IFNULL(totalDelivered,0)');
		$itemResult = $q->get();
		$openItems = $this->getItems($itemResult);

		return response()->json(
			[
				'openItems' => $openItems
			]);

	}

	public function getMonthItemCountSchedule()
	{

		$date = request()->date;
		$month = Carbon::parse($date)->format('m');
		$year = Carbon::parse($date)->format('Y');
		$monthlyItemCount = array();

		$monthSchedItems = PurchaseOrderSchedule::select('pods_scheduledate as date',
			Db::raw('count(*) as totalItem'))
		->has('item.po')
		->whereMonth('pods_scheduledate',$month)
		->whereYear('pods_scheduledate',$year)
		->groupBy('pods_scheduledate')
		->get();

		foreach($monthSchedItems as $row)
		{
			$name = "fy".Carbon::parse($row->date)->format('Ymd');
			$monthlyItemCount[$name] = $row->totalItem;
		}

		return response()->json(
			[
				'monthScheduledItems' => $monthlyItemCount
			]);
	}

	public function getPoItemSchedule($id)
	{

		$itemSched = PurchaseOrderItems::find($id)
		->schedule()
		->orderBy('pods_scheduledate', 'DESC')
		->get();

		$schedules = $this->getSchedules($itemSched);

		return response()->json(
			[
				'itemSchedule' => $schedules
			]
		);

	}

	public function addDailySchedule(Request $request)
	{
		$names = ['items' => 'scheduled items'];
		$date = $request->date;
		$validator = Validator::make($request->all(),[
			'date' => 'after_or_equal:'.date('Y-m-d').'|required',
			'items' => 'array|min:1'
		],[],$names);

		$error_msg = [];
		$itemError_msg = [];
		$addedItems = [];
		$addedItemKeys = [];

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		foreach($request->items as $key => $item)
		{
			if(!isset($item['schedQuantity']) || $item['schedQuantity'] === '' || $item['schedQuantity'] === null)
				continue;

			$poitem = PurchaseOrderItems::find($item['id']);
			$val = $this->getItem($poitem);
			$remaining = $val['quantity'] - $val['delivered_qty'];

			$validateItem = Validator::make($item,[
				'schedRemarks' => 'string|nullable|max:150',
				'schedQuantity' => 'integer|min:1|max:'.$remaining
			],['schedQuantity.max' => $val['itemdesc']." remaining quantity is ".$remaining]); 


			if($validateItem->fails()){ // if has errors
				$itemError_msg = array_merge($itemError_msg,$validateItem->errors()->all());
				continue;
			}

			$newItem = $poitem->schedule()->create([
				'pods_user_id' => auth()->user()->id,
				'pods_scheduledate' => $date,
				'pods_remaining' => $remaining,
				'pods_quantity' => $item['schedQuantity'],
				'pods_remarks' => isset($item['schedRemarks']) ? $item['schedRemarks'] : null
			]);

			array_push($addedItems,$this->getSchedule($newItem));
			array_push($addedItemKeys,$newItem->pods_item_id);

		}

		return response()->json(
			[
				'errors' => $itemError_msg,
				'newItems' => $addedItems,
				'itemKeys' => $addedItemKeys
			]
		);


	}

	public function updateItemSchedule(Request $request,$id)
	{
		$itemSched = PurchaseOrderSchedule::find($id);
		$val = $this->getItem($itemSched->item);
		$remaining = $val['quantity'] - $val['delivered_qty'];

		$validateItem = Validator::make($request->all(),[
			'remarks' => 'string|nullable|max:150',
			'quantity' => 'integer|min:1|max:'.$remaining
		],[
			'quantity.max' => $val['itemdesc']." remaining quantity is ".$remaining,
		]); 

		if($validateItem->fails()){
			return response()->json(['errors' => $validateItem->errors()->all()],422);
		}

		$itemSched->update(
			[
				'pods_quantity' => $request->quantity,
				'pods_remarks' => $request->remarks,
			]);


		$updatedItem = $this->getSchedule($itemSched);

		return response()->json(
			[
				'message' => 'Record updated',
				'updateItem' => $updatedItem,
			]);

	}

	public function deleteItemSchedule($ids)
	{

		$ids = json_decode('['.$ids.']', true);

		PurchaseOrderSchedule::destroy($ids);

		return response()->json(
			[
				'message' => 'Record/s deleted',
			]);

	}


}
