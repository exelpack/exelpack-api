<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Masterlist;
use App\Customers;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

class MasterlistController extends Controller
{

	public function cleanString($string){
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	public function getItem($item){

		$attachments = '';
		if($item->m_dwg !== '')
			$attachments .= '(with dwg)';
		if($item->m_bom !== '')
			$attachments .= '(with bom)';
		if($item->m_costing !== '')
			$attachments .= '(with costing)';

		return array(
			'id' => $item->id,
			'moq' => $item->m_moq,
			'mspecs' => strtoupper($item->m_mspecs),
			'itemdesc' => strtoupper($item->m_projectname),
			'partnum' => strtoupper($item->m_partnumber),
			'code' => strtoupper($item->m_code),
			'regisdate' => $item->m_regisdate,
			'effectdate' => $item->m_effectdate,
			'customer' => $item->m_customername,
			'requiredqty' => $item->m_requiredquantity,
			'outs' => $item->m_outs,
			'unit' => $item->m_unit,
			'unitprice' => $item->m_unitprice,
			'supplierprice' => $item->m_supplierprice,
			'remarks' => $item->m_remarks,
			'dwg' => $item->m_dwg,
			'bom' => $item->m_bom,
			'costing' => $item->m_costing,
			'budgetprice' => $item->m_budgetprice,
			'customername' => $item->m_customer_id,
			'attachment' => $attachments
		);


	}

	public function getItems($items){
		$item_arr = array();

		foreach($items as $item)
		{
			array_push($item_arr, $this->getItem($item));
		}
		return $item_arr;

	}

	public function getMasterlist()
	{

		$q = Masterlist::query();

		$list = $q->get();
		$itemList = $this->getItems($list);


		$responseArray = array(
			'itemList' => $itemList
		);
		
		return response()->json(
			[
				'itemList' => $itemList,
				// 'auth' => 
			]);

	}

	public function itemArray($item)
	{
		$moq = $item->moq ? $item->moq : 0;
		$partnum = $item->partnum ? $item->partnum : 'N/A';

		return array(
			'm_moq' => $moq,
			'm_mspecs' => $item->mspecs,
			'm_projectname' => $item->itemdesc,
			'm_partnumber' => $partnum,
			'm_code' => $item->code,
			'm_regisdate' => $item->regisdate,
			'm_effectdate' => $item->effectdate,
			'm_customername' => $item->customer,
			'm_requiredquantity' => $item->requiredqty,
			'm_outs' => $item->outs,
			'm_unit' => $item->unit,
			'm_unitprice' => $item->unitprice,
			'm_supplierprice' => $item->supplierprice,
			'm_remarks' => $item->remarks,
			'm_customer_id' => $item->customer_id
		);

	}

	public function addItem(Request $request)
	{

		$validator = Validator::make($request->all(),[
			'code' => 'required|max:50|regex:/^[a-zA-Z0-9-_]+$/',
			'mspecs' => 'required|max:255|regex:/^[a-zA-Z0-9-_ ()"]+$/',
			'itemdesc' => 'required|max:255|regex:/^[a-zA-Z0-9-_ (),"]+$/',
			'regisdate' => 'nullable|before_or_equal:'.date('Y-m-d'),
			'effectdate' => 'nullable|before_or_equal:'.date('Y-m-d'),
			'outs' => 'min:0|required',
			'requiredqty' => 'min:0|required',
			'unitprice' => 'nullable|min:0',
			'budgetprice' => 'nullable|min:0',
			'unit' => 'nullable|max:50',
			'supplierprice' => 'nullable|max:100',
			'remarks' => 'nullable|max:150',
			'customer' => 'required|max:150',
			'customer_id' => 'required',
			'partnum' => 'integer|min:0',
		]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item = new Masterlist();
		$newItem = $item->create($this->itemArray($request))->refresh();
		// if(isset($request->file)){

		// 	$dateissued = Carbon::now()->format('Y-m-d h:i:s');
		// 	$name = pathinfo($request->file->getClientOriginalName(),PATHINFO_FILENAME);
		// 	$ext = $request->file->getClientOriginalExtension();
		// 	$filename =  $name."_".Carbon::now()->timestamp.".".$ext;
		// 	Storage::disk('local')->putFileAs('/public/files/'.$request->product_id."/",$request->file, $filename);
		// 	$doesHaveFile = true;

		// }
		// $newItem->refresh();
		return $newItem;

	}	
}
