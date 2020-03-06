<?php

namespace App\Exports;

use App\PurchaseOrder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesExport implements FromArray, WithHeadings
{

    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {

    	$pos = PurchaseOrder::latest()->get();
    	$from = Carbon::parse(request()->from);
    	$to = Carbon::parse(request()->to);
    	$summaryFormat = request()->summary;
    	$conversion = intval(request()->conversion);
    	$customer_arr = [];

    	foreach($pos as $po){
    		$customer = $po->customer->companyname;
    		$total = $this->getPoTotal($po->poitems,$from,$to,$conversion,$summaryFormat);
    		if(!array_key_exists($customer, $customer_arr)){

    			$customer_arr[$customer] = array_merge(array(
    				'company_name' => strtoupper($customer),
    				'open_amount' => $total['openAmt'],
    				'sales_amount' => $total['salesAmt'],
    				'retention_amount' => $total['retentionAmt'],
    				'new_customer_amount' => $total['newCustomerAmt'],
    				'increase_amount' => $total['increaseAmt'],
    			),$total['salesSummary']);

    		}else{
    			$customer_arr[$customer]['open_amount'] += $total['openAmt'];
    			$customer_arr[$customer]['sales_amount'] += $total['salesAmt'];
    			$customer_arr[$customer]['retention_amount'] += $total['retentionAmt'];
    			$customer_arr[$customer]['new_customer_amount'] += $total['newCustomerAmt'];
    			$customer_arr[$customer]['increase_amount'] += $total['increaseAmt'];

    			foreach($total['salesSummary'] as $key => $sales){
    				if(!array_key_exists($key, $customer_arr[$customer]))
    					$customer_arr[$customer][$key] = $sales;
    				else
    					$customer_arr[$customer][$key] += $sales;
    			}

    		}
    	}
    	$tableCollect = collect($customer_arr);
    	$tableData = array();
    	$tableKeys = array();

    	$tableDataTotal = array();
    	$tableDataTotal['company_name'] = 'Total';

    	foreach($tableCollect->values() as $key => $row){
    		$keys = collect($row)->keys();
    		$data = array();
    		if($key == 0)
    			$tableKeys = $keys;

    		foreach($keys as $k){
    			$data[$k] = $row[$k];

    			if($k === "company_name"){
    				continue;
    			}

    			if(array_key_exists($k, $tableDataTotal))
    				$tableDataTotal[$k] += $row[$k];
    			else
    				$tableDataTotal[$k] = $row[$k];
    		}

    		array_push($tableData,$data);

    	}

    	array_push($tableData,$tableDataTotal);

    	$keys =  ['keys' => collect($tableKeys)
    			->mapWithKeys(function($key){
    				return [$key => strtoupper(str_replace('_',' ', $key))];
    			})
    			->all()];

    	return array_merge($keys,$tableData);

    }	

    public function headings(): array
    {
    	return [];
    }

    private function getPoTotal($items,$from,$to,$conversion,$summaryFormat = 'none'){

    	$openAmount = 0;
    	$salesAmount = 0;
    	$retentionAmount = 0;
    	$newCustomerAmount = 0;
    	$increaseAmount = 0;
    	$salesSummary = array();

    	foreach($items as $item){
    		$kpi = strtolower($item->poi_kpi);
    		$itemAmount = $item->poi_quantity * $item->poi_unitprice;
    		$open = $itemAmount - $item->delivery()->whereNotBetween('poidel_deliverydate',[$from,$to])
    		->sum('poidel_quantity') * $item->poi_unitprice;

    		$sales = $item->delivery()
    		->whereBetween('poidel_deliverydate',[$from,$to])
    		->sum('poidel_quantity') * $item->poi_unitprice;

    		$itemDeliveries = $item->delivery()->whereBetween('poidel_deliverydate',[$from,$to])
    		->orderBy('poidel_deliverydate', 'ASC')
    		->get();

    		if($summaryFormat !== 'none'){
    			foreach($itemDeliveries as $data){

    				$date = Carbon::parse($data->poidel_deliverydate);
    				$key = $date->format('Y-m-d');

    				if($summaryFormat == 'weekly')
    					$key = "Week_".$date->weekOfYear."_".$date->format('Y');
    				else if($summaryFormat == 'monthly')
    					$key = $date->format('M')."_".$date->format('Y');


    				if(!array_key_exists($key, $salesSummary))
    					$salesSummary[$key] = $data->poidel_quantity * $item->poi_unitprice;
    				else
    					$salesSummary[$key] += $data->poidel_quantity * $item->poi_unitprice;
    			}
    		}


    		if(strtoupper($item->po->po_currency) == 'USD'){
    			$open = $open * $conversion;
    			$sales = $sales * $conversion;
    		}

    		if($kpi == 'retention')
    			$retentionAmount += $sales;
    		else if($kpi == 'increase')
    			$increaseAmount += $sales;
    		else
    			$newCustomerAmount += $sales;

    		$openAmount+= ($open - $sales);
    		$salesAmount+= $sales;

    	}

    	return array(
    		'openAmt' => $openAmount,
    		'salesAmt' => $salesAmount,
    		'retentionAmt' => $retentionAmount,
    		'newCustomerAmt' => $newCustomerAmount,
    		'increaseAmt' => $increaseAmount,
    		'salesSummary' => $salesSummary,
    	);

    }
  }
