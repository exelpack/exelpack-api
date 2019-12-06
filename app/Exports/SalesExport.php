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

    	$pos = PurchaseOrder::all();
			$from = Carbon::parse(request()->from);
			$to = Carbon::parse(request()->to);
			$customer_arr = [];

			foreach($pos as $po){
				$customer = $po->customer->companyname;
				$total = $this->getPoTotal($po->poitems,$from,$to);

				if(!array_key_exists($customer, $customer_arr)){

					$customer_arr[$customer] = array(
						'cn' => strtoupper($customer),
						'openAmt' => $total['openAmt'],
						'salesAmt' => $total['salesAmt'],
						'retentionAmt' => $total['retentionAmt'],
						'newCustomerAmt' => $total['newCustomerAmt'],
						'increaseAmt' => $total['increaseAmt'],
					);

				}else{
					$customer_arr[$customer]['openAmt'] += $total['openAmt'];
					$customer_arr[$customer]['salesAmt'] += $total['salesAmt'];
					$customer_arr[$customer]['retentionAmt'] += $total['retentionAmt'];
					$customer_arr[$customer]['newCustomerAmt'] += $total['newCustomerAmt'];
					$customer_arr[$customer]['increaseAmt'] += $total['increaseAmt'];
				}
			}

			$tableData = $this->removeKeyNamesFromArray($customer_arr);

			array_push($tableData,array(
				'cn' => 'TOTAL',
				'openAmt' => array_sum(array_column($tableData,'openAmt')),
				'salesAmt' => array_sum(array_column($tableData,'salesAmt')),
				'retentionAmt' => array_sum(array_column($tableData,'retentionAmt')),
				'newCustomerAmt' => array_sum(array_column($tableData,'newCustomerAmt')),
				'increaseAmt' => array_sum(array_column($tableData,'increaseAmt'))
			));

			return $tableData;
		
		}	

	public function headings(): array
	{
		return [
			'CUSTOMER',
			'OPEN AMOUNT',
			'SALES AMOUNT',
			'RETENTION',
			'INCREASE',
			'NEW CUSTOMER',
		];
	}

	private function removeKeyNamesFromArray($datas){
		$new_arr = array();
		foreach($datas as $data){
			array_push($new_arr,$data);
		}
		return $new_arr;
	}

	private function getPoTotal($items,$from,$to){

		$openAmount = 0;
		$salesAmount = 0;
		$retentionAmount = 0;
		$newCustomerAmount = 0;
		$increaseAmount = 0;

		foreach($items as $item){
			$kpi = strtolower($item->poi_kpi);
			$itemAmount = $item->poi_quantity * $item->poi_unitprice;
			$open = $itemAmount - $item->delivery()->whereNotBetween('poidel_deliverydate',[$from,$to])
			->sum('poidel_quantity') * $item->poi_unitprice;

			$sales = $item->delivery()
			->whereBetween('poidel_deliverydate',[$from,$to])
			->sum('poidel_quantity') * $item->poi_unitprice;

			if(strtoupper($item->po->po_currency) == 'USD'){
				$open = $open * 50;
				$sales = $sales * 50;
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
		);

	}
}
