<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\AccountingPurchasesItems;

class AccountingPayablesReport implements FromView
{
 
  public function view(): View
  {
    $date = request()->month." 10 ,".request()->year;
    $month = new Carbon($date);

    $endMonth = $month->endOfMonth()->toDateTimeString();

    $data = AccountingPurchasesItems::doesntHave('ap')
      ->join('purchasesms_supplier', function($join){
        $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
      })
      ->orderBy('purchasesms_supplier.supplier_name','ASC')
      ->orderBy('item_salesinvoice_no','ASC')
      ->select('purchasesms_items.*')
      ->get()
      ->filter(function($item) use ($endMonth) {
        $date = new Carbon($item->item_datereceived);
        $date->addDays($item->supplier->supplier_payment_terms);
        $isDue = $date->lte($endMonth);
        return $isDue;
      })
      ->values();
    $payables = array();
    $php = 0;
    $usd = 0;
    $totalphp = 0;
    $totalusd = 0;
    $totalunreleased = 0;
    $count = count($data);
    foreach($data as $key => $row){
      $date = new Carbon($row->item_datereceived);
      $date->addDays($row->supplier->supplier_payment_terms);

      $supplier = $row->supplier;
      if($row->item_currency === 'PHP')
      {
        $php = $row->item_unitprice * $row->item_quantity;
        $usd = 0;
        $unreleased_amt = $php;
      }else{
        $php = 0;
        $usd = $row->item_unitprice * $row->item_quantity;
        $unreleased_amt = $usd;
      }
      $totalusd+= $usd;
      $totalphp+= $php;

      if($row->item_with_unreleasedcheck)
      {
        $status = 'UNRELEASED CHECK';
        $unreleased = $unreleased_amt;
        $totalunreleased+= $unreleased;
      }else{
        $status = 'NOT PAID';
        $unreleased = 0;
      }

      array_push($payables,
        array(
          'suppliers_name' => $supplier->supplier_name,
          'po' => $row->item_purchaseorder_no,
          'si' => $row->item_salesinvoice_no,
          'dr' => $row->item_deliveryreceipt_no,
          'particular' => $row->item_particular,
          'purchasedate' => $row->item_datepurchased,
          'duedate' => $date->format('d/m/Y'),
          'remarks' => '',
          'status' => $status,
          'php' => $php,
          'usd' => $usd,
          'unreleased_amt' => $unreleased,
        )
      );

      if($count - 1 == $key || $data[$key + 1]->item_supplier_id !== $row->item_supplier_id)
      {
        array_push($payables,
          array(
            'suppliers_name' => $supplier->supplier_name." TOTAL",
            'po' => '',
            'si' => '',
            'dr' => '',
            'particular' => '',
            'purchasedate' => '',
            'duedate' => '',
            'remarks' => '',
            'status' => '',
            'php' => $totalphp,
            'usd' => $totalusd,
            'unreleased_amt' => $totalunreleased
          )
        );
        $totalphp = 0;
        $totalusd = 0;
        $totalunreleased = 0;
      }
    }
    $company = request()->company;
    return view('purchasesms.PurchasesPayablesReport',
      compact('payables','month','company')
    );
  }
}
