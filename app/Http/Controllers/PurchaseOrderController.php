<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\Masterlist;
use App\Customers;
use DB;

class PurchaseOrderController extends Controller
{

	public function cleanString($string){
		$string = str_replace(","," ",$string);//replace comma with space
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	public function test()// for testing purposes
	{
		$t = PurchaseOrder::all();
		return $this->getPos($t);
	}

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
			'poi_others' => $item['others'],
			'poi_remarks' => $item['remarks'],
		];
	}

	public function getPo($po)
	{
		$items = $this->getItems($po->poitems);
		$hasJo = $po->poitems()->has('jo')->count() > 0 ? true : false;
		$totalQuantity = $po->getTotalItemQuantity->totalQuantity;
		$totalDelivered = intval($po->getTotalDeliveryQuantity->totalDelivered);
		$status = $po->poitems()->count() > 0 ? $totalDelivered >= $totalQuantity 
		? 'SERVED' : 'OPEN' : 'NO ITEM/s';
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
			'withJo' => $item->jo()->count() > 0 ? true : false,
			'isNotEditable' => $status === 'SERVED' ? true : false,
		);

	}

	public function poIndex()
	{
		$pageSize = request()->pageSize;

		$getDelivery = DB::table('cposms_poitemdelivery')->select('poidel_item_id',DB::raw('sum(poidel_quantity + poidel_underrun_qty) as totalDeliveredQty'))->groupBy('poidel_item_id');
		$q = PurchaseOrder::query();
		$q->whereHas('poitems' , function($q1) use ($getDelivery){
			$q1->from('cposms_purchaseorderitem')
				->leftJoinSub($getDelivery,'getDelivery', function($join){
				$join->on('cposms_purchaseorderitem.id','=','getDelivery.poidel_item_id');
			})
			->select(Db::raw('sum(poi_quantity) as totalItemQty'));
			
			$q1->having('totalItemQty','>','getDelivery.totalDeliveredQty');
		});

		$po_result = $q->orderBy('id','desc')->paginate($pageSize);
		$po = $this->getPos($po_result);
		$customers = Customers::select('id','companyname')->orderBy('companyname','ASC')->get();
		$itemSelectionList	= Masterlist::select('id','m_projectname as itemdesc',
			'm_partnumber as partnum','m_code as code','m_unit as unit','m_unitprice as unitprice')->get();


		return response()->json(
			[
				'customers' => $customers,
				'po' => $po,
				'poLength' => $po_result->total(),
				'itemSelectionList' => $itemSelectionList
			]);
	}

	public function poItemsIndex()
	{
		$pageSize = request()->pageSize;
		$q = PurchaseOrderItems::query();
		$poItems_result = $q->has('po')->orderBy('id','desc')->paginate($pageSize);
		$poItems = $this->getItems($poItems_result);

		return response()->json(
			[
				'poItems' => $poItems,
				'poItemsLength' => $poItems_result->total(),
			]);
	}

	public function createPurchaseOrder(Request $request){

		$cleanPO = $this->cleanString($request->po_num);

		Validator::make($request->all(),
			[
				'po_num' => 'unique:cposms_purchaseorder,po_ponum|required|max:100',
				'customer' => 'required',
				'currency' => 'required',
			],[],['po_num' => 'purchase order number'])->validate();

		$po = new PurchaseOrder();
		$po->fill(
			[
				'po_customer_id' => $request->customer,
				'po_currency' => $request->currency,
				'po_date' => $request->date,
				'po_ponum' => $cleanPO,
			]);

		$po->save();

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

		Validator::make($request->all(),
			[
				'po_num' => 'unique:cposms_purchaseorder,po_ponum,'.$request->id.'|required|max:100',
				'customer' => 'required',
				'currency' => 'required',
			],[],['po_num' => 'purchase order number'])->validate();

		$po = PurchaseOrder::find($request->id);
		$po->update(
			[
				'po_customer_id' => $request->customer,
				'po_currency' => $request->currency,
				'po_date' => $request->date,
				'po_ponum' => $cleanPO,
			]);
		
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

		$newItem = $this->getPo(PurchaseOrder::find($request->id));
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
}
