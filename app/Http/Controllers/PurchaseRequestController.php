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
use App\PurchaseRequestSeries;
use App\Masterlist;
use App\PurchaseOrderItems;
use App\PurchaseOrder;
use App\Customers;
use App\Inventory;

class PurchaseRequestController extends LogsController
{
    //+
	public function cleanString($string){
		$string = str_replace(","," ",$string);//replace comma with space
		$string = trim(preg_replace('/\s+/', ' ', $string));
		return $string;
	}

	public function fetchPrSeries()
	{
		$series = PurchaseRequestSeries::first();

		$number = str_pad($series->series_number,5,"0",STR_PAD_LEFT);
		$joseries = $series->series_prefix.date('y'). "-".$number;
		return $joseries;
	}

	public function getJobOrders()
	{

		$sort = strtolower(request()->sort);
    $showRecord = strtolower(request()->showRecord);
    $purchaseOrderItem = PurchaseOrderItems::select(
        'id',
        'poi_po_id',
        'poi_itemdescription',
        'poi_code',
        'poi_quantity',
        'poi_partnum'
      )->groupBy('id');

    $purchaseOrder = PurchaseOrder::select('id', 'po_customer_id','po_ponum', 'po_currency')
      ->groupBy('id');

    $customer = Customers::select('id','companyname')
      ->groupBy('id');

    $produced = JobOrderProduced::select(Db::raw('sum(jop_quantity) as producedQty'),
      'jop_jo_id')->groupBy('jop_jo_id');

    $pr = Db::table('prms_prlist')
            ->select(
              Db::raw('count(*) as prCount'),
              'pr_jo_id',
              DB::raw('GROUP_CONCAT(pr_prnum) as prnumbers')
            )->groupBy('pr_jo_id');

    $q = JobOrder::has('poitems.po');
    // join
    $q->leftJoinSub($produced, 'prod', function($join){
      $join->on('prod.jop_jo_id','=','pjoms_joborder.id');
    })->leftJoinSub($purchaseOrderItem, 'item', function($join){
      $join->on('item.id','=','pjoms_joborder.jo_po_item_id');
    })->leftJoinSub($purchaseOrder, 'po', function($join){
      $join->on('po.id','=','item.poi_po_id');
    })->leftJoinSub($customer, 'customer', function($join){
      $join->on('customer.id','=','po.po_customer_id');
    })->leftJoinSub($pr, 'pr', function($join){
      $join->on('pr.pr_jo_id','=','pjoms_joborder.id');
    });
    //select
    $q->select(
      'pjoms_joborder.id as jo_id',
      'po_ponum as poNum',
      'jo_joborder as jo_num',
      'companyname as customer',
      'poi_code as code',
      'poi_itemdescription as itemDesc',
      'poi_partnum as partNumber',   
      'jo_dateissued as date_issued',
      'jo_dateneeded as date_needed',
      'jo_quantity as quantity',
      'jo_remarks as remarks',
      'jo_others as others',
      Db::raw('IF(jo_quantity > IFNULL(producedQty,0),"OPEN","SERVED" ) as status'),
      DB::raw('cast(IFNULL(prCount,0) as int) as prCount'),
      'prnumbers'
    );

    if(request()->has('search')){
      $search = "%".strtolower(request()->search)."%";
      $q->whereHas('poitems.po', function($q) use ($search){
        $q->where('po_ponum','LIKE', $search);
      })->orWhereHas('poitems', function($q) use ($search){
        $q->where('poi_itemdescription','LIKE',$search);
      })->orWhere('jo_joborder','LIKE',$search)
        ->orWhereRaw('prnumbers LIKE ?', [$search])
        ->orWhereRaw('customer LIKE ?', [$search])
        ->orWhereRaw('code LIKE ?', [$search]);

    }

    if($showRecord == 'open')
      $q->whereRaw('jo_quantity > IFNULL(producedQty,0)');
    else if($showRecord == 'served')
      $q->whereRaw('jo_quantity <= IFNULL(producedQty,0)');

    if(request()->has('customer')) {
      $q->whereRaw('companyname = ?',array(request()->customer));
    }

    if(request()->has('month')) {
      $q->whereMonth('jo_dateissued',request()->month);
    }

    if(request()->has('year')) {
      $q->whereYear('jo_dateissued',request()->year);
    }

    if($sort == 'desc'){
      $q->orderBy('pjoms_joborder.id','DESC');
    }else if($sort == 'asc'){
      $q->orderBy('pjoms_joborder.id','ASC');
    }else if($sort == 'di-desc'){
      $q->orderBy('jo_dateissued','DESC');
    }else if($sort == 'di-asc'){
      $q->orderBy('jo_dateissued','ASC');
    }else if($sort == 'jo-desc'){
      $q->orderBy('jo_joborder','DESC');
    }else if($sort == 'jo-asc'){
      $q->orderBy('jo_joborder','ASC');
    }

    $isFullLoad = request()->has('start') && request()->has('end');
    if($isFullLoad)
      $list = $q->offset(request()->start)->limit(request()->end)->get();
    else 
      $list = $q->paginate(1000);

    return response()->json(
      [
        'joListLength' => $isFullLoad ? intval(request()->end) : $list->total(),
        'joList' => $isFullLoad ? $list : $list->items(),
      ]
    );
	}

	public function getPr($pr)
	{
		$jo = $pr->jo;
		$po = $jo->poitems->po;
		$item = $jo->poitems;
		$items = $pr->pritems;

		return array(
			'id' => $pr->id,
			'pr_num' => $pr->pr_prnum,
			'customer' => $po->customer->companyname,
			'po_num' => $po->po_ponum,
			'jo_num' => $pr->jo->jo_joborder,
			'date' => $pr->pr_date,
			'code' => $item->poi_code,
			'quantity' => $item->poi_quantity,
			'item_desc' => $item->poi_itemdescription,
			'remarks' => $pr->pr_remarks,
			'isForPricing' => $pr->pr_forPricing,
			'hasPrice' => $pr->prpricing()->count() > 0,
			'status' => $pr->prpricing()->count() > 0 ? 'W/ PRICE' : 'NO PRICE',
			'item_no' => $items->count(),
			'items' => $items->map(function($data){
  				return $this->getPrItems($data);
			 })
    );
      
	}

	public function getPrItems($item)
	{
    $delivered = 0;
    $pricing = $item->pr->prpricing;

    if($pricing)
      $delivered = $pricing->po 
          ? $pricing->po->poitems()
              ->where('spoi_mspecs', $item->pri_mspecs)
              ->first()
              ->invoice()
              ->sum('ssi_receivedquantity')
          : 0;

		return array(
			'id' => $item->id,
			'pr_num' => $item->pr->pr_prnum,
			'code' => $item->pri_code,
			'mspecs' => $item->pri_mspecs,
			'unit' => $item->pri_uom,
			'quantity' => $item->pri_quantity,
			'remarks' => $item->pri_remarks,
      'delivered' => intval($delivered),
		);
	}

	public function getPrList()
	{

		$q = PurchaseRequest::query();
		$pageSize = request()->pageSize;
		$sort = strtolower(request()->sort);
		$showRecord = strtolower(request()->showRecord);
		$q->whereHas('jo.poitems.po');

		if(request()->has('search')){

			$search = "%".strtolower(request()->search)."%";

			$q->whereHas('jo.poitems.po', function($q) use ($search){
				$q->where('po_ponum','LIKE', $search);
			})->orWhereHas('jo', function($q) use ($search){
				$q->where('jo_joborder','LIKE',$search);
			})->orWhere('pr_prnum','LIKE',$search);

		}

		if(request()->has('searchItem')){

			$searchItem = "%".strtolower(request()->searchItem)."%";

			$q->whereHas('pritems', function($q) use ($searchItem){
				$q->where('pri_code','LIKE', $searchItem)
					->orWhere('pri_mspecs','LIKE',$searchItem);
			});

		}

		if(trim($showRecord) != ''){
			if($showRecord == 'with')
				$q->where('pr_hasPrice',1);
			else if($showRecord == 'Pending')
				$q->where('pr_forPricing',1);
			else if($showRecord == 'not-forwarded')
				$q->where('pr_forPricing',0);
		}

		if($sort == 'desc'){
			$q->orderBy('id','DESC');
		}else if($sort == 'asc'){
			$q->orderBy('id','ASC');
		}else if($sort == 'date-desc'){
			$q->orderBy('pr_date','DESC');
		}else if($sort == 'date-asc'){
			$q->orderBy('pr_date','ASC');
		}

		$prResult = $q->paginate($pageSize);
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
			'pr_remarks' => $this->cleanString($data['remarks']),
		);
	}

	public function prItemArray($data)
	{
		$uom = $data['unit'];

		if($data['unit'] == '' || $data['unit'] == NULL)
			$uom = $data['master_unit'] || 'pc';

		return array(
			'pri_code' => $data['code'],
			'pri_mspecs' => $data['mspecs'],
			'pri_uom' => $uom,
			'pri_quantity' => $data['quantity'],
			'pri_remarks' => $data['remarks'],
		);
	}

	public function getPrItemDetails($id)
	{
		if(!request()->has('code')){
			return response()->json([
				'errors' => ['Code parameter is required']
			],422);
		}
    $code = request()->code;
    $jobOrder = JobOrder::findOrFail($id);
		$joQty = $jobOrder->jo_quantity;

		$prItems = PurchaseRequestItems::whereHas('pr',function($q) use ($id){
  			$q->where('pr_jo_id',$id);	
  		})
  		->get()
  		->map(function($data) {
  			return $this->getPrItems($data);
  		});

		$items = Masterlist::where('m_code','LIKE','%'.$code."%")
  		->get()
  		->reject(function($data) {
  			$countDash = count(explode("-",$data->m_code)) - 1;
  			return $countDash < 2;
  		})
  		->map(function($data) use($joQty) {
        $inventory_qty = Inventory::where('i_mspecs',$data->m_mspecs)->sum('i_quantity');
  			return array(
  				'id' => $data->id,
  				'code' => $data->m_code,
  				'mspecs' => $data->m_mspecs,
  				'quantity' => number_format(($joQty * $data->m_requiredquantity) / $data->m_outs, 2, '.',''),
  				'requiredQty' => $data->m_requiredquantity,
          'inventoryQty' => intval($inventory_qty),
  				'unit' => $data->m_unit,
  				'outs' => $data->m_outs,
  				'remarks' => $data->m_remarks,
  			);

  		})->values();
		return response()->json(
			[
				'pr_series' => $this->fetchPrSeries(),
				'prItemList' => $prItems,
				'items' => $items,
			]);
	}

  public function getPrItemDeliveryAndIssuance($id){
    $item = PurchaseRequestItems::findOrFail($id);
    $pricing = $item->pr->prpricing;

    //get delivery details
    if($pricing && $pricing->po){
      $delivered = $pricing->po->poitems()
        ->where('spoi_mspecs', $item->pri_mspecs)
        ->first()
        ->invoice()
        ->where('ssi_receivedquantity','>',0)
        ->where('ssi_rrnum','!=',NULL)
        ->get()
        ->map(function($del){
          return array(
            'id' => $del->id,
            'invoice' => $del->ssi_invoice,
            'dr' => $del->ssi_dr,
            'rr' => $del->ssi_rrnum,
            'date' => $del->ssi_date,
            'receivedQuantity' => $del->ssi_receivedquantity,
          );
        })
        ->toArray();
    }else
      $delivered = array();

    $issuance = $item->pr->jo
      ->outgoing()->whereHas('inventory', function($on) use ($item){
        $on->where('i_mspecs', $item->pri_mspecs);
      })->get()->map(function($outgoing) {
        return array(
          'id' => $outgoing->id,
          'mrNum' => $outgoing->out_mr_num,
          'date' => $outgoing->out_date,
          'quantity' => $outgoing->out_quantity,
          'remarks' => $outgoing->out_remarks,
        );
      });

    return response()->json([
      'delivered' => $delivered,
      'issuance' => $issuance,
    ]);
 
  }

	public function addPr(Request $request)
	{

		$validator = Validator::make($request->all(),
			[
				'jo_id' => 'required|integer',
				'pr_num' => 'required|string|max:60|unique:prms_prlist,pr_prnum',
				'date' => 'date|before_or_equal:'.date('Y-m-d'),
				'remarks' => 'nullable|max:200',
				'items' => 'array|min:1|required',
			],[],[
				'pr_num' => 'Purchase request No.',
				'items' => 'Purchase request items'
			]);

		$jobOrder = JobOrder::findOrFail($request->jo_id);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$pr = $jobOrder->pr()->create(
      array_merge($this->prArray($request->all()),
        array(
          'pr_user_id' => Auth()->user()->id,
        )
    ));

		PurchaseRequestSeries::first()
			->update(['series_number' => DB::raw('series_number + 1')]); //update series
			foreach($request->items as $data){
				$data['master_unit'] = $request->unit;
				$pr->pritems()->create($this->prItemArray($data));
			}

			$pr->refresh();
			$this->logCreateDeletePrForJo($pr->jo->jo_joborder,$pr->pr_prnum,
				$pr->pritems()->count(),null,"Added");//log created;

			return response()->json(
				[
					'newItem' => $this->getPr($pr),
					'message' => 'Record added'
				]);
	}

	public function editPr(Request $request, $id)
	{
		$pr = PurchaseRequest::findOrFail($id);

		if($pr->pr_hasPrice && 
			 (auth()->user()->type != 'admin' || auth()->user()->type != 'management') ){
			return response()->json(['errors' => ['Record not editable']],422);
		}

		if($request->forwardPr){
			$pr->update(['pr_forPricing' => true]);
			$pr->refresh();
			return response()->json(
				[
					'newItem' => $this->getPr($pr),
					'message' => 'Record forwaded to purchasing'
				]);
		}

		$validator = Validator::make($request->all(),
			[
				'id' => 'required|integer',
				'pr_num' => 'required|string|max:60|unique:prms_prlist,pr_prnum,'.$id,
				'remarks' => 'nullable|max:200',
				'items' => 'array|min:1|required',
			],[],[
				'pr_num' => 'Purchase request No.',
				'items' => 'Purchase request items'
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$pr->fill($this->prArray($request->all()));

		if($pr->isDirty()){
			$this->logPrEdit($pr->pr_prnum,$pr->getOriginal()['pr_remarks'],$request->remarks);
			$pr->save();
		}

		$item_ids = array_column($request->items,'id'); //get request items id
		$pritem_ids = $pr->pritems()->pluck('id')->toArray(); //get pr items id

		foreach($pr->pritems as $item){
			if(!in_array($item->id,$item_ids)){ // delete item if didnt exist anymore on edited pr
				$pr->pritems()->find($item['id'])->delete();
				$this->logCreateDeletePrItem($pr->pr_prnum,$item['pri_code'],"Deleted");
			}
		}

		foreach($request->items as $item){ //adding and editing item
			if(in_array($item['id'],$pritem_ids)){ //check if item exists on po alr then update
				$pritem = $pr->pritems()->find($item['id'])->fill($this->prItemArray($item));

				if($pritem->isDirty()){
					$this->logPrItemEdit($pritem->getDirty(),$pritem->getOriginal(),
						$pritem->pr->pr_prnum,$pritem->pri_code);
					$pritem->save();
				}
			}else{
				//if item doesnt exist on po then add.
				$pritem = $pr->pritems()->create($this->prItemArray($item));
				$this->logCreateDeletePrItem($pr->pr_prnum,$pritem->pri_code,"Added");
			}
		}
		$pr->refresh();

		return response()->json(
			[
				'newItem' => $this->getPr($pr),
				'message' => 'Record updated'
			]);

	}

	public function deletePr($id)
	{
		$pr = PurchaseRequest::findOrFail($id);
		$jonum = $pr->jo->jo_joborder;
		$prnum = $pr->pr_prnum;
		$prItemCount = $pr->pritems()->count();

		if(!request()->has('remarks')){
			return response(['errors' => ['Remarks parameter is required']],422);
		}

		if($pr->pr_hasPrice){
			return response()->json(['errors' => ['Record not deletable']],422);
		}
		
		$pr->pritems()->delete();
		
		$pr->delete();

		$this->logCreateDeletePrForJo($jonum,$prnum,$prItemCount,request()->remarks,"Deleted");//log created;

		return response()->json(
			[
				'message' => 'Record deleted',
				'deletedId' => $id
			]);
	}
}
