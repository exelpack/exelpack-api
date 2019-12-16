<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Masterlist;
use App\Inventory;
use App\InventoryIncoming;
use App\InventoryOutgoing;

use Carbon\Carbon;
use Validator;

class InventoryController extends Controller
{
	private $inventoryValidation = array(
		'mspecs' => 'required|string|max:255',
		'itemdesc' => 'required|string|max:255',
		'partnum' => 'required|string|max:150',
		'unitprice' => 'nullable|numeric|min:1',
		'unit' => 'nullable|string|max:50',
		'quantity' => 'nullable|integer|min:1',
		'min' => 'nullable|integer|min:1',
		'max' => 'nullable|integer|min:1',
	);

	private $inventoryName = array(
		'mspecs' => 'Material specification',
		'itemdesc' => 'Item description',
		'partnum' => 'Part number',
		'unitprice' => 'Unit price',
	);

	public function getMasterlistItems()
	{

		$masterlist = Masterlist::select(
			'id',
			'm_mspecs as mspecs',
			'm_projectname as itemdesc',
			'm_partnumber as partnum',
			'm_code as code',
			'm_unit as unit',
			'm_unitprice as unitprice')
		->doesntHave('inventory')
		->get();

		return response()->json(
			[
				'masterlistOpt' => $masterlist
			]);

	}

	public function getInventoryItem($item)
	{
		$withUpdate = Masterlist::where([
			['m_mspecs', $item->i_mspecs],
			['m_projectname', $item->i_projectname],
			['m_partnumber', $item->i_partnumber],
			['m_code', $item->i_code],
		])->count();

		return array(
			'id' => $item->id,
			'mspecs' => $item->i_mspecs,
			'itemdesc' => $item->i_projectname,
			'partnum' => $item->i_partnumber,
			'code' => $item->i_code,
			'unit' => $item->i_unit,
			'unitprice' => $item->i_unitprice,
			'quantity' => $item->i_quantity,
			'min' => $item->i_min,
			'max' => $item->i_max,
			'withUpdate' => $withUpdate > 0 ? false : true
		);
	}	

	public function getInventoryItems()
	{

		$inventory = Inventory::all();
		$items_arr = array();

		foreach($inventory as $item)
		{
			array_push($items_arr,$this->getInventoryItem($item));
		}

		return response()->json(
			[
				'inventoryList' => $items_arr
			]);

	}

	public function createInvetoryItem(Request $request)
	{

		$validator = Validator::make(
			$request->all(),
			array_merge($this->inventoryValidation,[
				'code' => 'required|string|max:50|unique:wims_inventory,i_code'
			]),
			[],
			$this->inventoryName
		);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$inventory = new Inventory();
		$inventory->fill(
			[
				'i_mspecs' => $request->mspecs,
				'i_projectname' => $request->itemdesc,
				'i_partnumber' => $request->partnum,
				'i_code' => $request->code,
				'i_unitprice' => $request->unitprice,
				'i_unit' => $request->unit,
				'i_quantity' => $request->quantity,
				'i_min' => $request->min,
				'i_max' => $request->max,
			]);
		$inventory->save();

		$newItem = $this->getInventoryItem($inventory);
		return response()->json(
			[
				'newItem' => $newItem,
				'message' => 'Record Added'
			]);

	}

	public function editInventoryItem(Request $request,$id)
	{

		$validator = Validator::make(
			$request->all(),
			array_merge($this->inventoryValidation,[
				'code' => 'required|string|max:50|unique:wims_inventory,i_code,'.$id
			]),
			[],
			$this->inventoryName
		);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$inventory = Inventory::findOrFail($id);
		$inventory->fill(
			[
				'i_mspecs' => $request->mspecs,
				'i_projectname' => $request->itemdesc,
				'i_partnumber' => $request->partnum,
				'i_code' => $request->code,
				'i_unitprice' => $request->unitprice,
				'i_unit' => $request->unit,
				'i_quantity' => $request->quantity,
				'i_min' => $request->min,
				'i_max' => $request->max,
			]);

		if($inventory->isDirty()){
			$inventory->save();
		}
		$newItem = $this->getInventoryItem($inventory);
		return response()->json(
			[
				'newItem' => $newItem,
				'message' => 'Record updated'
			]);

	}

	public function deleteInventoryItem($id)
	{

		$inventory = Inventory::findOrFail($id);
		$inventory->delete();

		return response()->json(
			[
				'message' => 'Record deleted',
			]);
	}

	//incoming
	public function createInventoryIncoming(Request $request)
	{

		$validator = Validator::make($request->all(),
			array(
				'id' => 'required|integer|min:1',
				'quantity' => 'integer|min:1|required',
				'date' => 'required|before_or_equal:'.date('Y-m-d'),
				'remarks' => 'nullable|max:250'
			)
		);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item = Inventory::findOrFail($request->id);
		$newQty = $item->i_quantity + $request->quantity;
		$incoming = $item->incoming()->create([
			'inc_quantity' => $request->quantity,
			'inc_newQuantity' => $newQty,
			'inc_date' => $request->date,
			'inc_remarks' => $request->remarks,
			'inc_spoi_id' => 0
		]);
		$incoming->save();
		$item->update([
			'i_quantity' => $newQty
		]);

		$newItem = $this->getInventoryItem($item);
		return response()->json(
			[
				'message' => 'Successfully added '.$request->quantity." quantity.",
				'newItem' => $newItem,
			]);

	}

	public function getInventoryIncoming()
	{

		$incomings = InventoryIncoming::latest()->get();
		$incoming_arr = array();

		foreach($incomings as $incoming)
		{
			$diffInHrs = $incoming->created_at->diffInMinutes(Carbon::now());

			array_push($incoming_arr, 
				array(
					'id' => $incoming->id,
					'code' => $incoming->inventory->i_code,
					'mspecs' => $incoming->inventory->i_mspecs,
					'quantity' => $incoming->inc_quantity,
					'newQuantity' => $incoming->inc_newQuantity,
					'date' => $incoming->inc_date,
					'remarks' => $incoming->inc_remarks,
					'isDeletable' => $diffInHrs > 480 ? false : true
				));

		}

		return response()->json(
			[
				'incomingList' => $incoming_arr
			]);

	}

	public function deleteIncoming($id)
	{

		$incoming = InventoryIncoming::findOrFail($id);
		$diffInHrs = $incoming->created_at->diffInMinutes(Carbon::now());
		$inv_qty = $incoming->inventory->i_quantity;

		if($diffInHrs > 480){
			return response()->json(
				[
					'errors' => ['Record cannot be deleted']
				],422);
		}

		if($incoming->inc_quantity >  $inv_qty)
		{
			return response()->json(
				[
					'errors' => ['Inventory quantity is lower than '.$incoming->inc_quantity]
				],422);
		}
		$incoming->inventory()->update(
			[
				'i_quantity' => $inv_qty - $incoming->inc_quantity
			]
		);
		$incoming->delete();

		return response()->json(
			[
				'message' => 'Record deleted',
			]
		);
		
	}

	//out going
	public function createInventoryOutgoing(Request $request)
	{
		$item = Inventory::findOrFail($request->id);

		$validator = Validator::make($request->all(),
			array(
				'id' => 'required|integer|min:1',
				'quantity' => 'integer|min:1|required|max:'.$item->i_quantity,
				'date' => 'required|before_or_equal:'.date('Y-m-d'),
				'remarks' => 'nullable|max:250',
				'mr_num' => 'string|nullable|max:50',
				'jo_id' => 'integer|min:0'
			)
		);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$newQty = $item->i_quantity - $request->quantity;
		$incoming = $item->outgoing()->create([
			'out_quantity' => $request->quantity,
			'out_newQuantity' => $newQty,
			'out_date' => $request->date,
			'out_remarks' => $request->remarks,
			'out_mr_num' => $request->mr_num,
			'out_jo_id' => $request->jo_id
		]);
		$incoming->save();
		$item->update([
			'i_quantity' => $newQty
		]);

		$newItem = $this->getInventoryItem($item);
		return response()->json(
			[
				'message' => 'Successfully less '.$request->quantity." quantity.",
				'newItem' => $newItem,
			]);

	}


	public function getInventoryOutgoing()
	{

		$outgoings = InventoryOutgoing::latest()->get();
		$outgoing_arr = array();

		foreach($outgoings as $outgoing)
		{
			$diffInHrs = $outgoing->created_at->diffInMinutes(Carbon::now());
			$jo = $outgoing->jo;
			array_push($outgoing_arr, 
				array(
					'id' => $outgoing->id,
					'code' => $outgoing->inventory->i_code,
					'mspecs' => $outgoing->inventory->i_mspecs,
					'quantity' => $outgoing->out_quantity,
					'newQuantity' => $outgoing->out_newQuantity,
					'date' => $outgoing->out_date,
					'remarks' => $outgoing->out_remarks,
					'details' => $jo->jo_joborder ." / ".$jo->poitems->po->po_ponum,
					'isDeletable' => $diffInHrs > 480 ? false : true
				));

		}

		return response()->json(
			[
				'outgoingList' => $outgoing_arr
			]);

	}

	public function deleteOutgoing($id)
	{

		$outgoing = InventoryOutgoing::findOrFail($id);
		$diffInHrs = $outgoing->created_at->diffInMinutes(Carbon::now());
		$inv_qty = $outgoing->inventory->i_quantity;

		if($diffInHrs > 480){
			return response()->json(
				[
					'errors' => ['Record cannot be deleted']
				],422);
		}

		$outgoing->inventory()->update(
			[
				'i_quantity' => $inv_qty + $outgoing->out_quantity
			]
		);
		$outgoing->delete();

		return response()->json(
			[
				'message' => 'Record deleted',
			]
		);
		
	}

}
