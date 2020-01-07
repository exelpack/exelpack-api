<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

use App\JobOrder;
use App\JobOrderProduced;
use App\PurchaseRequest;
use App\PurchaseRequestItems;

class PurchaseRequestController extends LogsController
{
    //+
	public function cleanString($string){
		$string = str_replace(","," ",$string);//replace comma with space
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	public function getJobOrders()
	{

		$pageSize = request()->pageSize;
		$sort = strtolower(request()->sort);
		$showRecord = strtolower(request()->showRecord);
		$subProd = JobOrderProduced::select(Db::raw('sum(jop_quantity) as totalProduced'),
			'jop_jo_id')->groupBy('jop_jo_id');


		$q = JobOrder::query();

		$q->has('poitems.po');

		if(request()->has('search')){

			$search = "%".strtolower(request()->search)."%";

			$q->whereHas('poitems.po', function($q) use ($search){
				$q->where('po_ponum','LIKE', $search);
			})->orWhereHas('poitems', function($q) use ($search){
				$q->where('poi_itemdescription','LIKE',$search)
				->orWhere('poi_partnum','LIKE',$search);
			})->orWhere('jo_joborder','LIKE',$search);

		}

		if($showRecord == 'open' || $showRecord == 'served'){

			$q->from('pjoms_joborder')
			->leftJoinSub($subProd,'produced',function ($join){
				$join->on('pjoms_joborder.id','=','produced.jop_jo_id');				
			});

			if($showRecord == 'open')
				$q->whereRaw('jo_quantity > IFNULL(totalProduced,0)');
			else 
				$q->whereRaw('jo_quantity <= IFNULL(totalProduced,0)');

		}

		$q->where('jo_forwardToWarehouse',1);

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


		$joResult = $q->paginate($pageSize);
		
		$joList = $joResult->map(function ($jo,$key){

			$po = $jo->poitems->po;
			$item = $jo->poitems;
			$totalJo = $jo->poitems->jo()->sum('jo_quantity');
			$remaining = ($item->poi_quantity - $totalJo) + $jo->jo_quantity;

			$producedQty = intval($jo->produced()->sum('jop_quantity'));
			$status = $jo->jo_quantity > $producedQty ? 'OPEN' : 'SERVED';

			$hasPr = $jo->pr()->count();
			return array(
				'jo_id' => $jo->id,
				'po_num' => $po->po_ponum,
				'customer' => $po->customer->companyname,
				'code' => $item->poi_code,
				'part_num' => $item->poi_partnum,
				'item_desc' => $item->poi_itemdescription,
				'jo_num' => $jo->jo_joborder,
				'date_issued' => $jo->jo_dateissued,
				'date_needed' => $jo->jo_dateneeded,
				'quantity' => $jo->jo_quantity,
				'remarks' => $jo->jo_remarks,
				'status' => $status,
				'forwardToWarehouse' => $jo->jo_forwardToWarehouse,
				'prCount' => $hasPr,
				'hasPr' => $hasPr > 0
			);
		});

		return response()->json(
			[
				'joList' => $joList,
				'joListLength' => $joResult->total()
			]);

	}

	public function getPr($pr)
	{
		$jo = $pr->jo;
		$po = $jo->poitems->po;
		$items = $pr->pritems;

		return array(
			'id' => $pr->id,
			'customer' => $po->customer->companyname,
			'po' => $po->po_ponum,
			'jo' => $pr->jo->jo_jonum,
			'item_no' => $items->count(),
			'items' => $items->map(function($data){
				return $this->getPrItems($data);
			}),
		);

	}

	public function getPrItems($item)
	{

		return array(
			'id' => $item->id,
			'pr_num' => $item->pr->pr_prnum,
			'code' => $item->pri_code,
			'mspecs' => $item->pri_mspecs,
			'projectname' => $item->pri_projectname,
			'uom' => $item->pri_uom,
			'quantity' => $item->pri_quantity,
		);

	}

	public function getPrList()
	{

		$q = PurchaseRequest::query();


		$prResult = $q->paginate(10);
		$prList = $prResult->map(function ($pr) {
			return $this->getPr($pr);
		});

		return response()->json(
			[
				'prList' => $prList,
				'prListLength' => $prResult->total()
			]);

	}

	public function prArray($data)
	{
		return array(
			'pr_prnum' => $data['pr_num'],
			'pr_date' => $data['date'],
			'pr_remarks' => $data['remarks'],
		);
	}

	public function prItemArray($data)
	{
		return array(
			'pri_code' => $data['code'],
			'pri_mspecs' => $data['mspecs'],
			'pri_projectname' => $data['projectname'],
			'pri_uom' => $data['uom'],
			'pri_quantity' => $data['quantity'],
		);
	}

	public function getPrItemDetails($id)
	{
		if(!request()->has('code')){
			return response()->json([
				'errors' => ['Code parameter is required']
			],422);
		}

		$prItems = PurchaseRequestItems::whereHas('pr',function($q) use ($id){
			$q->where('pr_jo_id',$id);	
		})->get();

		return response()->json(
			[
				'prItemList' => $prItems
			]);


	}

	public function addPr(Request $request)
	{

		$validator = Validator::make($request->all(),
			[
				'id' => 'required|integer',
				'pr_num' => 'required|string|max:60|unique:prms_prlist,pr_prnum',
				'date' => 'date|before_or_equal:'.date('Y-m-d'),
				'remarks' => 'nullable|max:200',
				'pr_items' => 'array|min:1|required'
			],[],[
				'pr_num' => 'Purchase Request No.',
				'pr_items' => 'Purchase Request Items'
			]);

		$jobOrder = JobOrder::findOrFail($request->id);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$pr = $jobOrder->pr()
		->create($this->prArray($request->all()));

		foreach($request->pr_items as $data){
			$pr->pritems()->create($this->prItemArray($data));
		}

		$pr->refresh();

		return response()->json(
			[
				'newItem' => $this->getPr($pr)
			]);
		
	}

}