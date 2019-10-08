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
		$t = Masterlist::select('id','m_projectname as item_desc','m_partnumber as partnumber','m_code as code')->get();
		return $t;
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
			'po_itemcount' => $item->po->poitems()->count(),
		);

	}

	public function poItemsIndex()
	{
		$pageSize = request()->pageSize;
		$q = PurchaseOrderItems::query();
		$poItems_result = $q->orderBy('id','desc')->paginate($pageSize);
		$poItems = $this->getItems($poItems_result);

		$itemSelectionList	= Masterlist::select('id','m_projectname as itemdesc','m_partnumber as partnum','m_code as code','m_unit as unit','m_unitprice as unitprice')->get();
		$customers = Customers::select('id','companyname')->orderBy('companyname','ASC')->get();
		return response()->json(
			[
				'customers' => $customers,
				'poItems' => $poItems,
				'itemSelectionList' => $itemSelectionList,
				'poItemsLength' => $poItems_result->total()
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
				'po_ponum' => $cleanPO,
			]);

		$po->save();

		foreach($request->items as $row){

			$po->poitems()->create(
				[
					'poi_code' => $row['code'],
					'poi_partnum' => $row['partnum'],
					'poi_itemdescription' => $row['itemdesc'],
					'poi_quantity' => $row['quantity'],
					'poi_unit' => $row['unit'],
					'poi_unitprice' => $row['unitprice'],
					'poi_deliverydate' => $row['deliverydate'],
					'poi_kpi' => $row['kpi'],
					'poi_others' => $row['others'],
					'poi_remarks' => $row['remarks'],
				]);

		}
		
		$newItem = $this->getItems($po->poitems);
		return response()->json([
			'newItem' => $newItem
		]);

	}



}
