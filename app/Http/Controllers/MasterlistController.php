<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\LogsController;
use App\Masterlist;
use App\Customers;
use App\MasterlistConversion;

use DB;
use Excel;
use PDF;
use Storage;
use Carbon\Carbon;

use App\Exports\MasterlistExport;

class MasterlistController extends LogsController
{

	private $itemValidationRules = array();

	// exports
	public function exportMasterlist()
	{
		return Excel::download(new MasterlistExport, 'masterlist.xlsx');
	}

	public function __construct(){
		$this->itemValidationRules = array(
			'mspecs' => 'required|max:255',//|regex:/^[a-zA-Z0-9-_ ().\/"]+$/',
			'itemdesc' => 'required|max:255',//|regex:/^[a-zA-Z0-9-_ (),.\/"]+$/',
			'regisdate' => 'nullable|before_or_equal:'.date('Y-m-d'),
			'effectdate' => 'nullable|before_or_equal:'.date('Y-m-d'),
			'outs' => 'min:0|required',
			'requiredqty' => 'min:0|required',
			'unitprice' => 'nullable|min:0',
			'budgetprice' => 'nullable|min:0',
			'unit' => 'nullable|max:50',
			'supplierprice' => 'nullable|max:100',
			'weight' => 'nullable|integer|min:0',
			'remarks' => 'nullable|max:150',
			'partnum' => 'string|nullable',
			'customer' => 'required|min:1',
			'conversions' => 'array',
			'dwg' => 'nullable|mimes:pdf|max:5000',
			'bom' => 'nullable|mimes:pdf|max:5000',
			'costing' => 'nullable|mimes:pdf|max:5000',
		);
	}

	private $itemValidationName = array(
		'mspecs' => 'material specification',
		'itemdesc' => 'item description',
		'regisdate' => 'registration date',
		'effectdate' => 'effectivity date',
		'requiredqty' => 'required qty',
		'unitprice' => 'unit price',
		'budgetprice' => 'budget price',
		'supplierprice' => 'supplier price',
		'customer_id' => 'customer'
	);

	public function cleanString($string){
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	public function getItem($item){
		$attachment = '';

		if($item->m_dwg !== NULL)
			$attachment .= "(w/ drawing)";
		if($item->m_bom !== NULL)
			$attachment .= "(w/ bom)";
		if($item->m_costing !== NULL)
			$attachment .= "(w/ costing)";

		return array(
			'id' => $item->id,
			'moq' => $item->m_moq,
			'mspecs' => $item->m_mspecs,
			'itemdesc' => $item->m_projectname,
			'partnum' => $item->m_partnumber,
			'code' => strtoupper($item->m_code),
			'regisdate' => $item->m_regisdate,
			'effectdate' => $item->m_effectdate,
			'customer_label' => $item->customer ? strtoupper($item->customer->companyname) : "None",
			'customer' => $item->m_customer_id,
			'requiredqty' => $item->m_requiredquantity,
			'outs' => $item->m_outs,
			'unit' => $item->m_unit,
			'weight' => $item->m_weight,
			'unitprice' => $item->m_unitprice,
			'supplierprice' => $item->m_supplierprice,
			'remarks' => $item->m_remarks,
			'budgetprice' => $item->m_budgetprice,
			'dwg' => $item->m_dwg,
			'bom' => $item->m_bom,
			'costing' => $item->m_costing,
			'attachment' => $attachment,
			'conversions' => $item->conversions->map(function($data) {
				return $data->id;
			}),
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

	public function getCustomerList()
	{
		$customers = Customers::select('id','companyname')->orderBy('companyname','ASC')->get();

		return response()->json(
			[
				'customerList' => $customers
			]);
	}

	public function getConversions()
	{
		$conversions = MasterlistConversion::all();

		return response()->json(
			[
				'conversions' => $conversions
			]);
	}

	public function getMasterlist()
	{

		$list = Masterlist::with('customer')->orderBy('id','desc')->get();
		$itemList = $this->getItems($list);
		return response()->json(
			[
				'itemList' => $itemList,
			]);

	}

	public function itemArray($item)
	{
		$moq = $item->moq ? $item->moq : 0;
		$partnum = $item->partnum ? $item->partnum : 'NA';

		return array(
			'm_moq' => $moq,
			'm_mspecs' => strtoupper($this->cleanString($item->mspecs)),
			'm_projectname' => strtoupper($this->cleanString($item->itemdesc)),
			'm_partnumber' => strtoupper($this->cleanString($partnum)),
			'm_code' => strtoupper($this->cleanString($item->code)),
			'm_regisdate' => $item->regisdate,
			'm_effectdate' => $item->effectdate,
			'm_requiredquantity' => $item->requiredqty,
			'm_outs' => $item->outs,
			'm_weight' => $item->weight,
			'm_unit' => $item->unit,
			'm_unitprice' => $item->unitprice,
			'm_supplierprice' => $item->supplierprice,
			'm_remarks' => $item->remarks,
			'm_customer_id' => $item->customer,
			'm_budgetprice' => $item->budgetprice
		);

	}

	public function addAttachment($id,$attachment,$type){

		$ts = Carbon::now()->timestamp;
		$name = pathinfo($attachment->getClientOriginalName(),PATHINFO_FILENAME);
		$ext = $attachment->getClientOriginalExtension();
		$filename =  $name."_".$type."_".$ts.".".$ext;
		Storage::disk('local')->putFileAs('/pmms/files/'.$id.'/',$attachment, $filename);
		return $filename;
	}

	public function downloadAttachment($id,$type)
	{
		$item = Masterlist::findOrFail($id);
		$fileName = '';
		$type = strtolower($type);
		if($type == 'dwg' && ($item->m_dwg != '' || $item->m_dwg != null))
			$fileName = $item->m_dwg;
		else if($type == 'bom' && ($item->m_bom != '' || $item->m_bom != null))
			$fileName = $item->m_bom;
		else if($type == 'costing' && ($item->m_costing != '' || $item->m_costing != null))
			$fileName = $item->m_costing;
		else{
			return response()->json(
				[
					'errors' => ['File not found']
				]);
		}
		
		return Storage::download('pmms/files/'.$id."/".$fileName,$fileName.'.pdf',
			['content-type' => '']);
	}

	public function addItem(Request $request)
	{
		$validator = Validator::make($request->all(),
			array_merge($this->itemValidationRules,[
				'code' => 'required|max:50|regex:/^[a-zA-Z0-9-_]+$/|unique:pmms_masterlist,m_code',
			]),
			[],
			$this->itemValidationName);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item = new Masterlist();
		$createdItem = $item->create($this->itemArray($request))->refresh();
		$this->createDeleteLogForMasterlistItem(
			"Added",$request->code,$request->itemdesc,$request->mspecs
		);

		if(isset($request->dwg)){
			$filename = $this->addAttachment($createdItem->id,$request->dwg,"dwg");
			$createdItem->m_dwg = $filename;
		}

		if(isset($request->bom)){
			$filename = $this->addAttachment($createdItem->id,$request->bom,"bom");
			$createdItem->m_bom = $filename;
		}

		if(isset($request->costing)){
			$filename = $this->addAttachment($createdItem->id,$request->costing,"cost");
			$createdItem->m_costing = $filename;
		}
		$createdItem->conversions()->sync($request->conversions);
		$createdItem->save();
		$newItem = $this->getItem($createdItem);
		return response()->json([
			'newItem' => $newItem,
			'message' => 'Record added'
		]);


	}


	public function editItem(Request $request,$id)
	{

		$validator = Validator::make($request->all(),
			array_merge($this->itemValidationRules,[
				'code' => 'required|max:50|regex:/^[a-zA-Z0-9-_]+$/|unique:pmms_masterlist,m_code,'.$id,
			]),
			[],
			$this->itemValidationName);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item = Masterlist::findOrFail($id);
		$item->fill($this->itemArray($request));
		$item->conversions()->sync($request->conversions);
		if($item->isDirty())
		{
			$this->editLogForMasterlistItem($item->getDirty(),$item->getOriginal());
			$item->save();
		}

		$newItem = $this->getItem($item);

		return response()->json([
			'newItem' => $newItem,
			'message' => "Record updated"
		]);

	}

	public function addAttachmentToItem(Request $request)
	{

		$validator = Validator::make($request->all(),
			[
				'dwg' => 'mimetypes:application/pdf|max:5000|nullable',
				'bom' => 'mimetypes:application/pdf|max:5000|nullable',
				'costing' => 'mimetypes:application/pdf|max:5000|nullable',
				'item_id' => 'integer|required|min:1',
				'type' => 'string|required|max:7|min:1|in:DWG,BOM,COSTING,dwg,bom,costing'
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$type = strtoupper($request->type);
		$item = Masterlist::find($request->item_id);

		if($type == 'DWG' && ($item->m_dwg == NULL || $item->m_dwg == '' )){
			$filename = $this->addAttachment($request->item_id,$request->dwg,"dwg");
			$item->m_dwg = $filename;
		}

		if($type == 'BOM' && ($item->m_bom == NULL || $item->m_bom == '' )){
			$filename = $this->addAttachment($request->item_id,$request->bom,"bom");
			$item->m_bom = $filename;
		}

		if($type == 'COSTING' && ($item->m_costing == NULL || $item->m_costing == '' )){
			$filename = $this->addAttachment($request->item_id,$request->costing,"cost");
			$item->m_costing = $filename;
		}
		$item->save();
		$this->addAndDeleteAttachmentMasterlistItemLog("Added",
			$type,$filename,$item->m_code,$item->m_projectname);
		$newItem = $this->getItem($item);


		return response()->json([
			'message' => 'Attachment added',
			'newItem' => $newItem
		]);

	}

	public function deleteItem($id)
	{
		$item = Masterlist::find($id);
		$code = $item->m_code;
		$itemdesc = $item->m_projectname;
		$mspecs = $item->m_mspecs;
		Storage::deleteDirectory('/pmms/files/'.$id);
		$item->conversions()->detach();
		$item->delete();

		$this->createDeleteLogForMasterlistItem(
			"Deleted",$code,$itemdesc,$mspecs
		);

		return response()->json([
			'deletedId' => $id,
			'message' => 'Record deleted'
		]);
	}


	public function deleteAttachment($id)
	{
		if(!request()->has('type')){
			return response()->json(
				[
					'errors' => ['Attachment type is required']
				]);
		}	


		$item = Masterlist::findOrFail($id);
		$type = strtoupper(request()->type);
		$attachmentName = '';

		if($type == 'DWG' && ($item->m_dwg != NULL || $item->m_dwg != '' )){
			$attachmentName = $item->m_dwg;
			$item->m_dwg = null;
		}else if($type == 'BOM' && ($item->m_bom != NULL || $item->m_bom != '' )){
			$attachmentName = $item->m_bom;
			$item->m_bom = null;
		}else if($type == 'COSTING' && ($item->m_costing != NULL || $item->m_costing != '' )){
			$attachmentName = $item->m_costing;
			$item->m_costing = null;
		}else{
			return response()->json(
				[
					'errors' => ['Invalid attachment type'] //return error if invalid type
				]);
		}


		$item->save();
		$this->addAndDeleteAttachmentMasterlistItemLog("Deleted",
			$type,$attachmentName,$item->m_code,$item->m_projectname);
		Storage::delete('/pmms/files/'.$id."/".$attachmentName);// delete file
		$newItem = $this->getItem($item);
		return response()->json([
			'message' => 'Attachment deleted',
			'newItem' => $newItem
		]);
	}
}
