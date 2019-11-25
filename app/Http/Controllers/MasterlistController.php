<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Masterlist;
use App\MasterlistAttachments;
use App\Customers;

use DB;
use Excel;
use PDF;
use Storage;
use Carbon\Carbon;

class MasterlistController extends Controller
{
	private $itemValidationRules = array();

	public function __construct(){
		$this->itemValidationRules = array(
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
			'customer_id' => 'required',
			'partnum' => 'integer|min:0',
			'attachments' => 'array|min:1',
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
			'customer' => $item->customer->companyname,
			'customer_id' => $item->m_customer_id,
			'requiredqty' => $item->m_requiredquantity,
			'outs' => $item->m_outs,
			'unit' => $item->m_unit,
			'unitprice' => $item->m_unitprice,
			'supplierprice' => $item->m_supplierprice,
			'remarks' => $item->m_remarks,
			'budgetprice' => $item->m_budgetprice,
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
			'm_requiredquantity' => $item->requiredqty,
			'm_outs' => $item->outs,
			'm_unit' => $item->unit,
			'm_unitprice' => $item->unitprice,
			'm_supplierprice' => $item->supplierprice,
			'm_remarks' => $item->remarks,
			'm_customer_id' => $item->customer_id
		);

	}

	public function addAttachments($attachments,$id){
		$errorMsg = array();
		foreach($attachments as $attachment){

			$validator = Validator::make(array('attachment' => $attachment),
				['attachment' => 'mimes:pdf|max:2000']
			);

			if($validator->fails()){ // if has errors
				$itemError_msg = array_merge($errorMsg,$validator->errors()->all());
				continue;
			}

			$date = Carbon::now()->timestamp;
			$name = pathinfo($attachment->getClientOriginalName(),PATHINFO_FILENAME);
			$ext = $attachment->getClientOriginalExtension();
			$filename =  $name."_".$date.".".$ext;
			Storage::disk('local')->putFileAs('/pmms/files/'.$id.'/',$attachment, $filename);
			MasterlistAttachments::create([
				'ma_itemid' => $id,
				'ma_attachment' => $filename,
			]);
		}

		return $errorMsg;

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
		$uploadError = $this->addAttachments($request->attachments,$createdItem->id);

		$newItem = $this->getItem($createdItem);
		
		return response()->json([
			'newItem' => $newItem,
			'uploadError' => $uploadError
		]);

	}


	public function editItem(Request $request,$id)
	{

		$$validator = Validator::make($request->all(),
			array_merge($this->itemValidationRules,[
				'code' => 'required|max:50|regex:/^[a-zA-Z0-9-_]+$/|unique:pmms_masterlist,m_code,'.$id,
			]),
			[],
			$this->itemValidationName);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$item = Masterlist::find($id);
		$updatedItem = $item->update($this->itemArray($request));
		$newItem = $this->getItem($updatedItem);
		
		return response()->json([
			'newItem' => $newItem
		]);

	}

	public function addAttachmentsToItem(Request $request)
	{

		$validator = Validator::make($request->all(),
			[
				'attachments' => 'array|min:1',
				'item_id' => 'integer|required|min:1'
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		foreach($request->attachments as $attachment){

			$date = Carbon::now()->timestamp;
			$name = pathinfo($attachment->getClientOriginalName(),PATHINFO_FILENAME);
			$ext = $attachment->getClientOriginalExtension();
			$filename =  $name."_".$date.".".$ext;
			Storage::disk('local')->putFileAs('/pmms/files/'.$id.'/',$attachment, $filename);
			MasterlistAttachments::create([
				'ma_itemid' => $id,
				'ma_attachment' => $filename,
			]);

		}

	}

	public function deleteItem($id)
	{
		$item = Masterlist::find($id);
		$item->attachments()->delete();
		Storage::deleteDirectory('/pmms/files/'.$id);
		$item->delete();

		return response()->json([
			'deletedId' => $id,
			'message' => 'Record deleted'
		]);
	}

	public function setAttachmentViewability(Request $request,$id)
	{

		$validator = Validator::make($request->all(),
			[
				'view' => 'boolean|required'
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		MasterlistAttachments::find($id)->update([
				'ma_isPublic' => $request->view,
			]);

		return response()->json(
			[
				'message' => 'Record updated'
			]);	

	}

	public function viewItemAttachments($id)
	{

		$attachments = Masterlist::find($id)
			->attachments()
			->select('ma_attachment as attachment', 'ma_isPublic as isPublic')
			->get();

		return response()->json(
			[
				'attachments' => $attachments
			]);

	}

	public function deleteAttachment($id)
	{
			$attachment = MasterlistAttachments::find($id);
			$item_id = $attachment->ma_itemid;
			$attachment = $attachment->ma_attachment;
			Storage::delete('/pmms/files/'.$item_id."/".$attachment);// delete file
			$attachment->delete();
			MasterlistAttachments::create([
				'ma_itemid' => $id,
				'ma_attachment' => $filename,
			]);

			return response()->json(
					'deletedId' => $id,
					'message' => 'Attachment deleted'
				]);
	}


}
