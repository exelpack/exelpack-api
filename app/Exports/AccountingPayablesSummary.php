<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\AccountingPurchasesItems;

class AccountingPayablesSummary implements FromView
{
  private $monthsArray = array(
    'jan' => 1,
    'feb' => 2,
    'mar' => 3,
    'apr' => 4,
    'may' => 5,
    'jun' => 6,
    'jul' => 7,
    'aug' => 8,
    'sep' => 9,
    'oct' => 10,
    'nov' => 11,
    'dec' => 12,
  );

  public function view(): View
  {
    $month = request()->month;
    $year = request()->year;
    $tempDate = $month." 10, ".$year;
    $date = new Carbon($tempDate);
    $endMonth = $date->endOfMonth()->toDateTimeString();


    $items = AccountingPurchasesItems::join('purchasesms_supplier', function($join){
        $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
      })
      ->doesntHave('ap')
      ->select('purchasesms_items.*')
      ->whereMonth('item_datereceived',$this->monthsArray[$month])
      ->whereYear('item_datereceived',$year)
      ->orderBy('purchasesms_supplier.supplier_name','ASC')
      ->get();

    $ap = array();

    foreach($items as $item) {
      $supplier = $item->supplier;
      $supplierName = $supplier->supplier_name ?? 'undefined_customer';
      $supplierTerms = $supplier->supplier_payment_terms ?? 0;

      $total_accounts_payable_php = 0;
      $total_accounts_payable_usd = 0;
      //aging
      $first_php = 0;
      $first_usd = 0;
      $second_php = 0;
      $second_usd = 0;
      $third_php = 0;
      $third_usd = 0;
      $fourth_php = 0;
      $fourth_usd = 0;
      $fifth_php = 0;
      $fifth_usd = 0;
      $sixth_php = 0;
      $sixth_usd = 0;
      //accounts payable
      $accounts_payable_php = 0;    
      $accounts_payable_usd = 0;
      $unreleased_checked = 0;   

      $receivedDate = new Carbon($item->item_datereceived,'Asia/Singapore');
      $diff = $receivedDate->diffInDays($endMonth);

      $sum_amt = $item->item_unitprice * $item->item_quantity;
      if(strtoupper($item->item_currency) == 'PHP'){
        $total_accounts_payable_php += $sum_amt; 

        if($diff > 364)
            $sixth_php += $sum_amt;
        else if($diff > 119)
            $fifth_php += $sum_amt;
        else if($diff > 89)
            $fourth_php += $sum_amt;
        else if($diff > 59)
            $third_php += $sum_amt;
        else if($diff > 29)
            $second_php += $sum_amt;
        else
            $first_php += $sum_amt;

        if($diff >= $supplierTerms)
          $accounts_payable_php += $sum_amt;

      }else{
        $total_accounts_payable_usd += $sum_amt;  
        if($diff > 365)
            $sixth_usd += $sum_amt;
        else if($diff > 119)
            $fifth_usd += $sum_amt;
        else if($diff > 89)
            $fourth_usd += $sum_amt;
        else if($diff > 59)
            $third_usd += $sum_amt;
        else if($diff > 29)
            $second_usd += $sum_amt;
        else
            $first_usd += $sum_amt;

        if($diff >= $supplierTerms)
          $accounts_payable_usd += $sum_amt;
      }

      if($item->item_with_unreleasedcheck)
        $unreleased_checked += $sum_amt;

      if(!array_key_exists($supplierName, $ap))
        $ap[$supplierName] = array();


      $ap[$supplierName]['supplier_name'] = $supplierName;
      $ap[$supplierName]['total_accounts_payable_php'] = ($ap[$supplierName]['total_accounts_payable_php'] ?? 0) + $total_accounts_payable_php;
      $ap[$supplierName]['total_accounts_payable_usd'] = ($ap[$supplierName]['total_accounts_payable_usd'] ?? 0) + $total_accounts_payable_usd;
      $ap[$supplierName]['terms'] = $supplierTerms;
      //php aging
      $ap[$supplierName]['first_php'] = ($ap[$supplierName]['first_php'] ?? 0) + $first_php;
      $ap[$supplierName]['second_php'] = ($ap[$supplierName]['second_php'] ?? 0) + $second_php;
      $ap[$supplierName]['third_php'] = ($ap[$supplierName]['third_php'] ?? 0) + $third_php;
      $ap[$supplierName]['fourth_php'] = ($ap[$supplierName]['fourth_php'] ?? 0) + $fourth_php;
      $ap[$supplierName]['fifth_php'] = ($ap[$supplierName]['fifth_php'] ?? 0) + $fifth_php;
      $ap[$supplierName]['sixth_php'] = ($ap[$supplierName]['sixth_php'] ?? 0) + $sixth_php;
      //usd aging
      $ap[$supplierName]['first_usd'] = ($ap[$supplierName]['first_usd'] ?? 0) + $first_usd;
      $ap[$supplierName]['second_usd'] = ($ap[$supplierName]['second_usd'] ?? 0) + $second_usd;
      $ap[$supplierName]['third_usd'] = ($ap[$supplierName]['third_usd'] ?? 0) + $third_usd;
      $ap[$supplierName]['fourth_usd'] = ($ap[$supplierName]['fourth_usd'] ?? 0) + $fourth_usd;
      $ap[$supplierName]['fifth_usd'] = ($ap[$supplierName]['fifth_usd'] ?? 0) + $fifth_usd;
      $ap[$supplierName]['sixth_usd'] = ($ap[$supplierName]['sixth_usd'] ?? 0) + $sixth_usd;

      $ap[$supplierName]['accounts_payable_php'] = ($ap[$supplierName]['accounts_payable_php'] ?? 0) + $accounts_payable_php;
      $ap[$supplierName]['accounts_payable_usd'] = ($ap[$supplierName]['accounts_payable_usd'] ?? 0) + $accounts_payable_usd;
      $ap[$supplierName]['unreleased_checked'] = ($ap[$supplierName]['unreleased_checked'] ?? 0) + $unreleased_checked;

    }
    $company = request()->company ?? 'no_companyname';
    return view('purchasesms.PurchasesApSummary', 
      compact('ap','date','company')
    );
  }
}
