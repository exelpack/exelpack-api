<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\PurchaseOrderSchedule;

class PoDeliveryScheduleExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {

    	$date = request()->date;
    	$dailyScheds = PurchaseOrderSchedule::whereDate('pods_scheduledate',$date)
    	->has('item')
    	->get();
    	$scheds = $this->getSchedules($dailyScheds);
        return $scheds;
    }

    public function headings(): array
    {
    	return [
    		'CUSTOMER',
            'PURCHASE ORDER NO.',
    		'JO NO.',
    		'ITEM DESC',
    		'QUANTITY',
    		'REMARKS',
    		'PROD. QTY',
    		'PROD. REMARKS',
    		'OTHERS',
    	];
    }


    public function getSchedule($sched)
    {
    	return [
    		'customer' => $sched->item->po->customer->companyname,
    		'po' => $sched->item->po->po_ponum,
            'jo' => implode(",",$sched->item->jo->pluck('jo_joborder')->toArray()),
    		'itemdesc' => $sched->item->poi_itemdescription,
    		'quantity' => $sched->pods_quantity,
    		'remarks' => $sched->pods_remarks,
    		'commited_qty' => $sched->pods_commit_qty,
    		'prod_remarks' => $sched->pods_prod_remarks,
    		'others' => $sched->item->poi_others,
    	];

    }

    public function getSchedules($scheds)
    {

    	$sched_arr = [];
    	foreach($scheds as $sched){
    		array_push($sched_arr,$this->getSchedule($sched));
    	}

    	return $sched_arr;

    }
  }
