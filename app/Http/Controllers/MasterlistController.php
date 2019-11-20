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
	public function getItem($item){

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
		
		return response()->json(
			[
				'itemList' => $itemList
			]);

	}
}
