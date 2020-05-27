<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\AccountingPurchasesItems;

class AccountingPurchasesReport implements FromView
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
    $conversion = request()->conversion;
    $company = request()->company;
    $date = new Carbon($month." 10, ".$year);

    $data = AccountingPurchasesItems::join('purchasesms_supplier', function($join){
        $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
      })
      ->orderBy('purchasesms_supplier.supplier_name','ASC')
      ->orderBy('item_salesinvoice_no','ASC')
      ->select('purchasesms_items.*')
      ->whereMonth('item_datereceived',$this->monthsArray[$month])
      ->whereYear('item_datereceived',$year)
      ->get();
    $purchasesItems = array();
    $count = count($data);

    $totalusd = 0;
    $totalphp = 0;
    $totalzero = 0;
    foreach($data as $key => $row)
    {
      $supplier = $row->supplier;
      if($row->item_currency === 'PHP')
      {
        $zerorated = $row->item_unitprice * $row->item_quantity;
        $php = $zerorated;
        $usd = 0;
      }else{
        $php = 0;
        $usd = $row->item_unitprice * $row->item_quantity;
        $zerorated = $usd * $conversion;
      }
      $totalusd+= $usd;
      $totalphp+= $php;
      $totalzero+= $zerorated;

      $rdate = new Carbon($row->item_datereceived);
        array_push($purchasesItems,
          array(
            'suppliers_name' => $row->supplier->supplier_name,
            'code' => $row->account->accounts_name,
            'pr' => $row->item_purchaserequest_no,
            'po' => $row->item_purchaseorder_no,
            'si' => $row->item_salesinvoice_no,
            'dr' => $row->item_deliveryreceipt_no,
            'particular' => $row->item_particular,
            'purchasedate' => $rdate->format('d/m/Y'),
            'duedate' => $rdate
              ->addDays($supplier->supplier_payment_terms)
              ->format('d/m/Y'),
            'terms' => $supplier->supplier_payment_terms,
            'amountphp' => $php,
            'amountusd' => $usd,
            'zerorated' => $zerorated,
          )
        );

      if($count - 1 == $key || $data[$key + 1]->item_supplier_id !== $row->item_supplier_id)
      {
        array_push($purchasesItems,
          array(
            'suppliers_name' => $row->supplier->supplier_name." TOTAL",
            'code' => '',
            'pr' => '',
            'po' => '',
            'si' => '',
            'dr' => '',
            'particular' => '',
            'purchasedate' => '',
            'duedate' => '',
            'terms' => '',
            'amountphp' => $totalphp,
            'amountusd' => $totalusd,
            'zerorated' => $totalzero,
          )
        );
        $totalphp = 0;
        $totalusd = 0;
        $totalzero = 0;
      }
    }
    
    $company = request()->company;
    return view('purchasesms.PurchasesMonthlyReport',
      compact('purchasesItems', 'date', 'company')
    );
  }
}
