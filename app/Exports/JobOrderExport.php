<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\JobOrderProduced;
use App\JobOrder;
use DB;
class JobOrderExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
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

    	$joResult = $q->get();

    	return $this->getJos($joResult);
    }

    public function headings(): array
    {
    	return [
    		'STATUS',
    		'JOB ORDER',
    		'PURCHASE ORDER',
    		'CUSTOMER',
    		'DATE ISSUED',
    		'DATE NEEDED',
    		'CODE',
    		'PART NUMBER',
    		'ITEM DESCRIPTION',
    		'QUANTITY',
    		'PRODUCED QUANTITY',
    		'REMARKS',
    		'OTHERS',
    	];
    }

    public function getJo($jo)
    {
    	$po = $jo->poitems->po;
    	$item = $jo->poitems;
    	$totalJo = $jo->poitems->jo()->sum('jo_quantity');
    	$remaining = ($item->poi_quantity - $totalJo) + $jo->jo_quantity;

    	$producedQty = intval($jo->produced()->sum('jop_quantity'));
    	$status = $jo->jo_quantity > $producedQty ? 'OPEN' : 'SERVED';
    	return array(
    		'status' => $status,
    		'jo_num' => $jo->jo_joborder,
    		'po_num' => $po->po_ponum,
    		'customer' => $po->customer->companyname,
    		'date_issued' => $jo->jo_dateissued,
    		'date_needed' => $jo->jo_dateneeded,
    		'code' => $item->poi_code,
    		'part_num' => $item->poi_partnum,
    		'item_desc' => $item->poi_itemdescription,
    		'quantity' => $jo->jo_quantity,
    		'producedQty' => $producedQty,
    		'remarks' => $jo->jo_remarks,
    		'others' => $jo->jo_others,
    	);
    }

    public function getJos($jos)
    {
    	$jo_arr = array();

    	foreach($jos as $jo)
    	{
    		array_push($jo_arr,$this->getJo($jo));
    	}

    	return $jo_arr;
    }
  }
