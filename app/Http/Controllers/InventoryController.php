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
			'm_mspecs as mspecs',
			'm_projectname as itemdesc',
			'm_partnumber as partnum',
			'm_code as code',
			'm_unit as unit',
			'm_unitprice as unitprice')
		->doesntHave('inventory')
		->get();

		return $masterlist;

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
				'deletedId' => $id
			]);
	}

}
