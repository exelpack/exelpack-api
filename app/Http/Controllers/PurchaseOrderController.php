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
use App\Units;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

use App\Exports\PurchaseOrderExport;
use App\Exports\PurchaseOrderItemExport;
use App\Exports\PoDeliveryScheduleExport;
use App\Exports\PoItemDeliveredExport;
use App\Exports\SalesExport;

class PurchaseOrderController extends LogsController 
{

	public function cleanString($string){
		$string = str_replace(","," ",$string);//replace comma with space
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	// exports
	public function exportSales()
	{
		return Excel::download(new SalesExport, 'salesreport.xlsx');
	}

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

		$pdf = PDF::loadView('cposms.itemDailySchedule', $data)->setPaper('a4','landscape');
		return $pdf->download('Schedule_for_'.$date.'.pdf');

	}

	public function getOptionsPOSelect()
	{
		$customers = Customers::select('id','companyname')->orderBy('companyname','ASC')->get();
    $units = Units::all()->pluck('unit')->toArray();

		return response()->json(
			[
				'customers' => $customers,
				'unitsOption' => $units,
			]);
	}

  public function getCustomerItemsOptions()
  {
    if(!request()->has('customer'))
      return response()->json(['errors' => ['ID is required']], 422);

    $id = request()->customer;
    $customerItemList  = Masterlist::select(
        'id',
        'm_projectname as itemdesc',
        'm_partnumber as partnum',
        'm_code as code',
        'm_unit as unit',
        'm_unitprice as unitprice'
      )
      ->where('m_customer_id', $id)
      ->whereRaw('ROUND (   
        (
            LENGTH(m_code)
            - LENGTH( REPLACE ( m_code, "-", "") ) 
            ) / LENGTH("-")        
        ) = 1'
      )/////filter where dash occurence in string is only one meaning the code used is for item not materials
      ->latest('id')
      ->get();

    return response()->json(
      [
        'customerItemList' => $customerItemList,
        'customerId' => $id,
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
		$hasJo = $po->poitems()->has('jo')->count() > 0 ? true : false;
		$totalQuantity = $po->getTotalItemQuantity->totalQuantity;
		$totalDelivered = intval($po->getTotalDeliveryQuantity->totalDelivered);
		$status = $po->poitems()->count() > 0 ? $totalDelivered >= $totalQuantity 
		? 'SERVED' : 'OPEN' : 'NO ITEM';
		return array(
			'id' => $po->id,
			'po_num' => $po->po_ponum,
			'customerLabel' => $po->customer->companyname,
			'customer' => $po->po_customer_id,
			'date' => $po->po_date,
			'currency' => $po->po_currency,
      'isEndorsed' => $po->isEndorsed,
      'isForecast' =>  $po->po_isForeCast,
			'totalItems'=> $po->poitems()->count(),
			'totalQuantity'=> $totalQuantity,
			'totalDelivered'=> $totalDelivered,
			'status' => $status,
			'hasJo' => $hasJo,
		);

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

		//restrict
    $isEditable = true;

    if ($status === 'SERVED' || $item->jo()->count() > 0 || $delivered > 0)
      $isEditable = false;

		$hasDelivery = $item->delivery()->count() > 0 ? true : false;
		$hasSchedule = $item->schedule()->count() > 0 ? true : false;

		return array(
			'id' => $item->id,
      'key' => uniqid(),
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
      'delivered_qty' => $delivered,
			'kpi' => $item->poi_kpi,
			'others' => $item->poi_others,
			'remarks' => $item->poi_remarks,
      'hasDelivery' => $hasDelivery,
      'hasSchedule' => $hasSchedule,
      'isEditable' => $isEditable,
		);
	}

	public function poIndex()
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
      'cposms_purchaseorder.id as id',
      DB::raw('IF(IFNULL(delivery.totalDelivered,0) >= poi.totalQuantity,"SERVED","OPEN") as status'),
      DB::raw('UPPER(po_ponum) as po_num'),
      'customer.companyname as customerLabel',
      'customer.id as customer',
      'po_date as date',
      'po_currency as currency',
      'isEndorsed',
      'po_isForeCast as isForeCast',
      DB::raw('IFNULL(poi.totalItems,0) as totalItems'),
      DB::raw('IFNULL(poi.totalQuantity,0) as totalQuantity'),
      DB::raw('IFNULL(delivery.totalDelivered,0) as totalDelivered'),
      DB::raw('IF(IFNULL(jo.joCount,0) > 0,true,false) as hasJo'),
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

    $isFullLoad = request()->has('start') && request()->has('end');
    if($isFullLoad)
      $list = $q->offset(request()->start)->limit(request()->end)->get();
    else 
      $list = $q->paginate(1000);

    return response()->json(
      [
        'poLength' => $isFullLoad ? intval(request()->end) : $list->total(),
        'po' => $isFullLoad ? $list : $list->items(),
      ]
    );
	}

  public function getPoItems($id)
  {

    $po = PurchaseOrder::findOrFail($id);
    return response()->json([
      'items' => $this->getItems($po->poitems)
    ]);
  }

	public function poItemsIndex()
	{

		$pod = DB::table('cposms_poitemdelivery')->select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
			as totalDelivered'),'poidel_item_id',DB::raw('count(*) as deliveryCount'))->groupBy('poidel_item_id');

    $pos = DB::table('cposms_podeliveryschedule')->select(DB::raw('count(*) as schedCount'),'pods_item_id')
        ->groupBy('pods_item_id');

    $po = DB::table('cposms_purchaseorder')->select('id', 'po_ponum', 'po_currency', 'po_customer_id')
      ->groupBy('id');

    $customer = DB::table('customer_information')->select('id', 'companyname')
      ->groupBy('id');

    $q = PurchaseOrderItems::has('po')
      ->leftJoinSub($pod, 'delivery',function ($join){
        $join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');       
      })
      ->leftJoinSub($pos, 'sched', function($join){
        $join->on('sched.pods_item_id', '=', 'cposms_purchaseorderitem.id');
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('po.id','=','cposms_purchaseorderitem.poi_po_id');    
      })
      ->leftJoinSub($customer, 'customer', function($join){
        $join->on('customer.id','=','po.po_customer_id');    
      });

    $q->select([
      'cposms_purchaseorderitem.id as id',
      'customer.companyname as customer',
      'customer.id as customer_id',
      DB::raw('IF(IFNULL(delivery.totalDelivered,0) >= cposms_purchaseorderitem.poi_quantity,"SERVED","OPEN") as status'),
      'po.po_ponum as po_num',
      'poi_code as code',
      'poi_partnum as partnum',
      'poi_itemdescription as itemdesc',
      'poi_quantity as quantity',
      'poi_unit as unit',
      'po.po_currency as currency',
      'poi_unitprice as unitprice',
      'poi_deliverydate as deliverydate',
      DB::raw('IFNULL(delivery.totalDelivered,0) as delivered_qty'),
      DB::raw('IF(IFNULL(delivery.deliveryCount,0) > 0,true,false) as hasDelivery'),
      DB::raw('IF(IFNULL(sched.schedCount,0) > 0,true,false) as hasSchedule'),
      'poi_kpi as kpi',
      'poi_remarks as remarks',
    ]);

    if(request()->has('customer')){
      $q->whereRaw('customer.id = ?', array(request()->customer));
    }

    if(request()->has('status')){
      $status = strtolower(request()->status);
      if($status == 'served')
        $q->whereRaw('IFNULL(delivery.totalDelivered,0) >= poi_quantity');
      else
        $q->whereRaw('IFNULL(delivery.totalDelivered,0) < poi_quantity');
    }

    if(request()->has('search')){
      $search = "%".request()->search."%";
      $q->whereRaw('po.po_ponum like ?', array($search))
        ->whereRaw('poi_code like ?', array($search))
        ->whereRaw('poi_itemdescription like ?', array($search))
        ->whereRaw('poi_partnum like ?', array($search));
    }

    if(request()->has('deliveryDue')){
      $dateFilter = Carbon::now()->addDays(request()->deliveryDue);
      $q->whereDate('poi_deliverydate','<=', $dateFilter);
    }

    $sort = strtolower(request()->sort) ?? '';
    if($sort == 'date-asc')
      $q->orderBy('poi_deliverydate', 'ASC');
    else if($sort == 'date-desc')
      $q->orderBy('poi_deliverydate', 'DESC');
    else if($sort == 'latest')
      $q->latest('cposms_purchaseorderitem.id');
    else if($sort == 'oldest')
      $q->oldest('cposms_purchaseorderitem.id');

    $isFullLoad = request()->has('start') && request()->has('end');
    if($isFullLoad)
      $list = $q->offset(request()->start)->limit(request()->end)->get();
    else 
      $list = $q->paginate(1000);

    return response()->json(
      [
        'poItemsLength' => $isFullLoad ? intval(request()->end) : $list->total(),
        'poItems' => $isFullLoad ? $list : $list->items(),
      ]
    );
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

	public function editPurchaseOrder(Request $request,$id){

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

		$po = PurchaseOrder::findOrFail($request->id);
		$po->fill([
			'po_customer_id' => $request->customer,
			'po_currency' => $request->currency,
			'po_date' => $request->date,
			'po_ponum' => $cleanPO,
		]);

		if($po->isDirty()){
			$this->logPoEdit($po->getDirty(),$po->getOriginal(),$po->po_ponum);
			$po->save();
		}

		$items_ids = array_column($request->items,'id'); //get request items id

		//deletion of item
		foreach($po->poitems as $item){
			if(!in_array($item->id,$items_ids)){
				$this->logPoItemAddAndDelete($po->po_ponum,$item['poi_itemdescription'],"Deleted",$request->id);
				$po->poitems()->find($item['id'])->delete();
			}
		}

		foreach($request->items as $item){ //adding and editing item
			if(isset($item['id'])){ //check if item exists on po alr then update
				$poitem = $po->poitems()->find($item['id'])->fill($this->itemArray($item));

				if($poitem->isDirty()){
					$this->logPoItemEdit($poitem->getDirty(),$poitem->getOriginal(),$po->po_ponum);
					$poitem->save();
				}
			}else{
				//if item doesnt exist on po then add.
				$poitem = $po->poitems()->create($this->itemArray($item));
				$this->logPoItemAddAndDelete($po->po_ponum,$poitem->poi_itemdescription,"Added",$request->id);
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
		$this->logPoCancel($po->po_ponum,$remarks,$id);
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
    $recordCount = request()->has('recordCount') ? request()->recordCount : 500;
    $item = DB::table('cposms_purchaseorderitem')->select(
      'poi_itemdescription',
      'poi_partnum',
      'poi_code',
      'poi_po_id',
      'id'
    );

    $po = DB::table('cposms_purchaseorder')->select(
      'id',
      'po_ponum',
      'po_customer_id'
    );

    $customer = DB::table('customer_information')->select(
      'id',
      'companyname'
    ); 

		$q = PurchaseOrderDelivery::has('item.po')
          ->leftJoinSub($item, 'item', function($join){
            $join->on('cposms_poitemdelivery.poidel_item_id','=','item.id');
          })
          ->leftJoinSub($po, 'po', function($join){
            $join->on('item.poi_po_id','=','po.id');
          })
          ->leftJoinSub($customer, 'customer', function($join){
            $join->on('po.po_customer_id','=','customer.id');
          })
          ->select(
            'companyname as customer',
            'cposms_poitemdelivery.id',
            'poidel_quantity as quantity',
            'poidel_underrun_qty as underrun',
            'poidel_deliverydate as date',
            'poidel_invoice as invoice',
            'poidel_dr as dr',
            'poidel_remarks as remarks',
            'po.po_ponum as po_num',
            'item.poi_itemdescription as item_desc',
            'item.poi_partnum as partnum',
            'item.poi_code as code'
          );

		if(request()->has('start') && request()->has('end')){
			$start = request()->start;
			$end = request()->end;
			$q->whereBetween('poidel_deliverydate',[$start,$end]);
		}
		$deliveredItems = $q->latest('id')->limit($recordCount)->get();

		return response()->json(
			[
				'deliveredItems' => $deliveredItems,
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
		$ud =  $item->poidel_underrun_qty ?  $item->poidel_underrun_qty : 0;// set udnerrun to 0 if null
		$desc = 'Quantity : '.$request->quantity.", Underrun : ". $ud .", Date : ".$request->date;
		$this->logPoDeliveredCreateAndDelete($item->po->po_ponum,$item->id,"Added",$desc,$item->poi_itemdescription);//;log creation
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
				'totalQty' => 'integer|min:1|required|max:'.$remaining,
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

		$delivery->fill($this->itemDeliveryArray($request));
		if($delivery->isDirty()){
			$this->logPoDeliveredEdit($delivery->getDirty(),$delivery->getOriginal(),$item->po->po_ponum,$item->poi_itemdescription);
			$delivery->save();
		}
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
		$desc = 'Quantity : '.$delivery->poidel_quantity.", Underrun : "
		.$delivery->poidel_underrun_qty.", Date : ".$delivery->poidel_deliverydate;

		$delivery->delete();
		$item = PurchaseOrderItems::find($item_id);
		$newStats = $this->getItemDeliveryStats($item);
		$updatedItem = $this->getItem($item);

		$this->logPoDeliveredCreateAndDelete($item->po->po_ponum,$item_id,"Deleted",$desc,$item->poi_itemdescription);//;log creation

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
		$date = request()->date;
    $pod = DB::table('cposms_poitemdelivery')->select(DB::raw('sum(poidel_quantity + poidel_underrun_qty) 
      as totalDelivered'),'poidel_item_id',DB::raw('count(*) as deliveryCount'))->groupBy('poidel_item_id');

    $po = DB::table('cposms_purchaseorder')->select('id', 'po_ponum', 'po_currency', 'po_customer_id')
      ->groupBy('id');

    $customer = DB::table('customer_information')->select('id', 'companyname')
      ->groupBy('id');

    $q = PurchaseOrderItems::has('po')
      ->leftJoinSub($pod, 'delivery',function ($join){
        $join->on('cposms_purchaseorderitem.id','=','delivery.poidel_item_id');       
      })
      ->leftJoinSub($po, 'po', function($join){
        $join->on('po.id','=','cposms_purchaseorderitem.poi_po_id');    
      })
      ->leftJoinSub($customer, 'customer', function($join){
        $join->on('customer.id','=','po.po_customer_id');    
      });

    $q->whereDoesntHave('schedule', function($q) use ($date){
      $q->where('pods_scheduledate', $date);
    });

    $q->select(
      'cposms_purchaseorderitem.id as id',
      'customer.companyname as customer',
      'po.po_ponum as po_num',
      'poi_code as code',
      'poi_partnum as partnum',
      'poi_itemdescription as itemdesc',
      'poi_quantity as quantity',
      'poi_unit as unit',
      'po.po_currency as currency',
      'poi_unitprice as unitprice',
      'poi_deliverydate as deliverydate',
      DB::raw('IFNULL(delivery.totalDelivered,0) as delivered_qty')
    )
      ->whereRaw('poi_quantity > IFNULL(totalDelivered,0)');
    $openItems =  $q->get();
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
			$name = "fy".Carbon::parse($row->date)->format('Ymj');
			$monthlyItemCount[$name] = $row->totalItem;
		}

		return response()->json(
			[
				'monthScheduledItems' => $monthlyItemCount
			]);
	}

	public function getScheduleDates()
	{

		$date = PurchaseOrderSchedule::has('item.po')
		->groupBy('pods_scheduledate')
		->get()
		->pluck('pods_scheduledate')
		->toArray();

		return response()->json(
			[
				'dates' => $date,
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
		$previousCount = PurchaseOrderSchedule::where('pods_scheduledate',$date)->count();

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

		if(count($addedItemKeys) > 0){
			$this->logPoDeliveryCreateAndDelete($date,$previousCount,count($addedItemKeys),"Added");
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

		if(!request()->has('date'))
			return response()->json(['errors' => ['No date']],422);

		$date = request()->date;
		$previousCount = PurchaseOrderSchedule::where('pods_scheduledate',$date)->count();
		PurchaseOrderSchedule::destroy($ids);
		$this->logPoDeliveryCreateAndDelete($date,$previousCount,count($ids),"Deleted");

		return response()->json(
			[
				'message' => 'Record/s deleted',
			]);

	}

	//for tree data of item po
	private function getJoProduced($data){
		$produced_arr = array();
		foreach($data as $row){

			array_push($produced_arr,
				array(
					'title' => 'Date : '.$row->jop_date." - ".$row->jop_quantity,
					'key' => $row->jo->jo_joborder."-p-".$row->id,
				)
			);

		}	
		return $produced_arr;
	}

  private function getPrTree($data){
    $pr_arr = array();
    foreach($data as $row){

      array_push($pr_arr,
        array(
          'title' => $row->pr_prnum." - Date(".$row->pr_date.")",
          'key' => $row->jo->jo_joborder."-pr-".$row->id,
          'children' => array(
            array(
              'title' => 'Items ('.$row->pritems()->count().')',
              'key' => $row->jo->jo_joborder."-pr-itemlist-".$row->id,
              'children' => $row->pritems->map(function($item) use ($row){
                return array(
                  'title' => $item->pri_code." - ".$item->pri_mspecs." - ".$item->pri_quantity,
                  'key' => $row->jo->jo_joborder."-pr-item-".$item->id,
                );
              }),
            ),
            array(
              'title' => 'Purchase Order (0)',
              'key' => $row->jo->jo_joborder."-pr-po-".$row->id,
              'children' => array(),
            ),
          )
        )
      );

    } 
    return $pr_arr;
  }

	public function getItemOverallDetails($itemId)
	{

		$item = PurchaseOrderItems::findOrFail($itemId);

		$tree_data = array();
		$expandedKeys = array();

		foreach($item->jo as $key => $jo){
			$getProducedQty = $jo->produced()->sum('jop_quantity');
      $getPrCount = $jo->pr()->count();
			$status = $jo->jo_quantity > $getProducedQty ? 'OPEN' : 'SERVED';

			array_push($expandedKeys, $jo->jo_joborder);
			$data = array(
				'title' => $jo->jo_joborder." (Qty. ".$jo->jo_quantity." - Status : ".$status.")",
				'key' => $jo->jo_joborder,
				'children' => array(
					array(
						'title' => 'Produced '."(Qty. ".$getProducedQty.")",
						'key' => $jo->jo_joborder."-p",
						'children' => $this->getJoProduced($jo->produced)
					),
					array(
						'title' => 'Purchase Requisition ('.$getPrCount.')',
						'key' => $jo->jo_joborder."-pr",
						'children' => $this->getPrTree($jo->pr)
					)
				)
			);

			array_push($tree_data,$data);
		}

		return response()->json(
			[
				'treeData' => $tree_data,
				'treeDataParentKeys' => $expandedKeys
			]);
	}

	//sales report
	public function salesReport()
	{

		$pos = PurchaseOrder::latest()->get();
		$from = Carbon::parse(request()->from);
		$to = Carbon::parse(request()->to);
		$summaryFormat = request()->summary ?? 'none';
		$conversion = intval(request()->conversion);
		$customer_arr = [];

		

    $itemDeliveries = PurchaseOrderDelivery::has('item.po')
      ->whereBetween('poidel_deliverydate',[$from,$to])
      ->orderBy('poidel_deliverydate', 'ASC')
      ->get();
    $summaryKeys = array();
    $salesSummary = array();
    if($summaryFormat !== 'none'){
      foreach($itemDeliveries as $delivery){
        $cus = strtoupper($delivery->item->po->customer->companyname);
        $date = Carbon::parse($delivery->poidel_deliverydate);
        $key = $date->format('Y-m-d');

        if($summaryFormat == 'weekly')
          $key = "Week_".$date->weekOfYear."_".$date->format('Y');
        else if($summaryFormat == 'monthly')
          $key = $date->format('M')."_".$date->format('Y');

        if(!in_array($key, $summaryKeys))
          array_push($summaryKeys, $key);

        if(!array_key_exists($cus, $salesSummary))
          $salesSummary[$cus] = array();

        if(!array_key_exists($key, $salesSummary[$cus]))
          $salesSummary[$cus][$key] = $delivery->poidel_quantity * $delivery->item->poi_unitprice;
        else
          $salesSummary[$cus][$key] += $delivery->poidel_quantity * $delivery->item->poi_unitprice;
      }
    }

    foreach($pos as $po){
      $customer = $po->customer->companyname;
      $total = $this->getPoTotal($po->poitems,$from,$to,$conversion);

      if(!array_key_exists($customer, $customer_arr)){

        $customer_arr[$customer] = array(
          'company_name' => strtoupper($customer),
          'open_amount' => $total['openAmt'],
          'sales_amount' => $total['salesAmt'],
          'retention_amount' => $total['retentionAmt'],
          'new_customer_amount' => $total['newCustomerAmt'],
          'increase_amount' => $total['increaseAmt'],
        );
      }else{
        $customer_arr[$customer]['open_amount'] += $total['openAmt'];
        $customer_arr[$customer]['sales_amount'] += $total['salesAmt'];
        $customer_arr[$customer]['retention_amount'] += $total['retentionAmt'];
        $customer_arr[$customer]['new_customer_amount'] += $total['newCustomerAmt'];
        $customer_arr[$customer]['increase_amount'] += $total['increaseAmt'];        
      }
    }
    $collectCustomerArr = collect($customer_arr);
    $tableData = array();
    foreach($collectCustomerArr->values() as $customer){
      if(count($salesSummary) > 0){
        if(array_key_exists($customer['company_name'], $salesSummary))
          array_push($tableData, array_merge(
            $customer,
            $salesSummary[$customer['company_name']]
          ));

        continue;
      }else{
         array_push($tableData, $customer);
      }
  

    }
    $tableKeys =  array_merge(array(
      'company_name',
      'open_amount',
      'sales_amount',
      'retention_amount',
      'new_customer_amount',
      'increase_amount',
    ), $summaryKeys);

    $totalArray = array();
    foreach($tableKeys as $key)
    {
      if($key == 'company_name'){
        $totalArray[$key] = 'TOTAL';
        continue;
      }
       $totalArray[$key] = array_sum(array_column($tableData,$key));
    }

    array_push($tableData, $totalArray);

		return response()->json([
			'tableData' => $tableData,
			'tableKeys' => $tableKeys,
		]);
		
	}


	private function getPoTotal($items,$from,$to,$conversion,$summaryFormat = 'none'){

		$openAmount = 0;
		$salesAmount = 0;
		$retentionAmount = 0;
		$newCustomerAmount = 0;
		$increaseAmount = 0;
		$salesSummary = array();

		foreach($items as $item){
			$kpi = strtolower($item->poi_kpi);
			$itemAmount = $item->poi_quantity * $item->poi_unitprice;
			$open = $itemAmount - $item->delivery()->whereNotBetween('poidel_deliverydate',[$from,$to])
				->sum('poidel_quantity') * $item->poi_unitprice;

			$sales = $item->delivery()
			->whereBetween('poidel_deliverydate',[$from,$to])
			->sum('poidel_quantity') * $item->poi_unitprice;

			if(strtoupper($item->po->po_currency) == 'USD'){
				$open = $open * $conversion;
				$sales = $sales * $conversion;
			}

			if($kpi == 'retention')
				$retentionAmount += $sales;
			else if($kpi == 'increase')
				$increaseAmount += $sales;
			else
				$newCustomerAmount += $sales;
			
			$openAmount+= ($open - $sales);
			$salesAmount+= $sales;

		}

		return array(
			'openAmt' => $openAmount,
			'salesAmt' => $salesAmount,
			'retentionAmt' => $retentionAmount,
			'newCustomerAmt' => $newCustomerAmount,
			'increaseAmt' => $increaseAmount,
		);

	}

}
