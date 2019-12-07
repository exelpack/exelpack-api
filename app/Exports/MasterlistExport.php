<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Masterlist;
class MasterlistExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array() : array
    {
    	$q = Masterlist::query();

    	$list = $q->get();
    	$itemList = $this->getItems($list);
    	return $itemList;
    }

    public function headings(): array
    {
    	return [
    		'CUSTOMER',
    		'CODE',
    		'MOQ',
    		'MATERIAL SPECIFICATION',
    		'ITEM DESCRIPTION',
    		'PARTNUM',
    		'REGISTRATION DATE',
    		'EFFECTIVITY DATE',
    		'REQUIRED QTY',
    		'OUTS',
    		'UNIT',
    		'UNIT PRICE',
    		'SUPPLIER PRICE',
    		'BUDGET PRICE',
    		'REMARKS',
    	];
    }

    public function getItem($item){

    	return array(
    		'customer_label' => $item->customer->companyname,
    		'code' => $item->m_code,
    		'moq' => $item->m_moq,
    		'mspecs' => $item->m_mspecs,
    		'itemdesc' => $item->m_projectname,
    		'partnum' => $item->m_partnumber,
    		'regisdate' => $item->m_regisdate,
    		'effectdate' => $item->m_effectdate,
    		'requiredqty' => $item->m_requiredquantity,
    		'outs' => $item->m_outs,
    		'unit' => $item->m_unit,
    		'unitprice' => $item->m_unitprice,
    		'supplierprice' => $item->m_supplierprice,
    		'budgetprice' => $item->m_budgetprice,
    		'remarks' => $item->m_remarks,
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

  }
