<?php

namespace App\Exports;

use App\PurchaseOrder;
use App\PurchaseOrderDelivery;
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
      $summaryFormat = request()->summary ?? 'none';
      $conversion = intval(request()->conversion);
      $customer_arr = [];

      $itemDeliveries = PurchaseOrderDelivery::has('item.po')
        ->whereBetween('poidel_deliverydate',[$from,$to])
        ->orderBy('poidel_deliverydate', 'ASC')
        ->get();
      $summaryKeys = array();
      $salesSummary = array();
      if($summaryFormat !== 'none'){
        foreach($itemDeliveries as $delivery){
          $cus = strtoupper($delivery->item->po->customer->companyname);
          $date = Carbon::parse($delivery->poidel_deliverydate);
          $key = $date->format('Y-m-d');

          if($summaryFormat == 'weekly')
            $key = "Week_".$date->weekOfYear."_".$date->format('Y');
          else if($summaryFormat == 'monthly')
            $key = $date->format('M')."_".$date->format('Y');

          if(!in_array($key, $summaryKeys))
            array_push($summaryKeys, $key);

          if(!array_key_exists($cus, $salesSummary))
            $salesSummary[$cus] = array();

          if(!array_key_exists($key, $salesSummary[$cus]))
            $salesSummary[$cus][$key] = $delivery->poidel_quantity * $delivery->item->poi_unitprice;
          else
            $salesSummary[$cus][$key] += $delivery->poidel_quantity * $delivery->item->poi_unitprice;
        }
      }

      foreach($pos as $po){
        $customer = $po->customer->companyname;
        $total = $this->getPoTotal($po->poitems,$from,$to,$conversion);

        if(!array_key_exists($customer, $customer_arr)){

          $customer_arr[$customer] = array(
            'company_name' => strtoupper($customer),
            'open_amount' => $total['openAmt'],
            'sales_amount' => $total['salesAmt'],
            'retention_amount' => $total['retentionAmt'],
            'new_customer_amount' => $total['newCustomerAmt'],
            'increase_amount' => $total['increaseAmt'],
          );
        }else{
          $customer_arr[$customer]['open_amount'] += $total['openAmt'];
          $customer_arr[$customer]['sales_amount'] += $total['salesAmt'];
          $customer_arr[$customer]['retention_amount'] += $total['retentionAmt'];
          $customer_arr[$customer]['new_customer_amount'] += $total['newCustomerAmt'];
          $customer_arr[$customer]['increase_amount'] += $total['increaseAmt'];        
        }
      }
      $collectCustomerArr = collect($customer_arr);
      $tableData = array();
      foreach($collectCustomerArr->values() as $customer){
        if(count($salesSummary) > 0){
          if(array_key_exists($customer['company_name'], $salesSummary))
            array_push($tableData, array_merge(
              $customer,
              $salesSummary[$customer['company_name']]
            ));

          continue;
        }else{
           array_push($tableData, $customer);
        }
    

      }
      $tableKeys =  array_merge(array(
        'company_name',
        'open_amount',
        'sales_amount',
        'retention_amount',
        'new_customer_amount',
        'increase_amount',
      ), $summaryKeys);

      $totalArray = array();
      foreach($tableKeys as $key)
      {
        if($key == 'company_name'){
          $totalArray[$key] = 'TOTAL';
          continue;
        }
         $totalArray[$key] = array_sum(array_column($tableData,$key));
      }

      array_push($tableData, $totalArray);
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
      );
    }
  }
