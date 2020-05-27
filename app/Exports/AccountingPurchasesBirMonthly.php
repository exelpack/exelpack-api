<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\AccountingPurchasesItems;

class AccountingPurchasesBirMonthly implements FromView
{
  private $conversion;
  private $year;
  private $month;
  private $company;

  public function __construct($conversion, $year, $month, $company){
    $this->conversion = $conversion;
    $this->year = $year;
    $this->month = $month;
    $this->company = $company;
  }
    
  public function view(): View
  {
    $date = $this->month." 10, ".$this->year;
    $fixedDate = new Carbon($date);
    $from = $fixedDate->format("Y-m")."-11";
    $newDate = new Carbon($from);
    $to = $newDate->addMonth()->format("Y-m")."-10";

    $data = AccountingPurchasesItems::join('purchasesms_supplier', function($join){
        $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
      })
      ->select('purchasesms_items.*')
      ->orderBy('purchasesms_supplier.supplier_name','ASC')
      ->orderBy('item_salesinvoice_no','ASC')
      ->whereBetween('item_datereceived',[$from,$to])
      ->get();

    $purchasesItems = array();
    $conversion = $this->conversion;
    $count = count($data);
    $accountsTotal = array();
    $accountsTotalAll = 0;
    $totalusd = 0;
    $totalphp = 0;
    $totalzero = 0;
    
    foreach($data as $key => $row)
    { 
      $account = $row->account;
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

      if(array_key_exists($account->accounts_name, $accountsTotal))
        $accountsTotal[$account->accounts_name] += $totalzero;
      else
        $accountsTotal[$account->accounts_name] = $totalzero;

        $accountsTotalAll+= $totalzero;


      $pdate = new Carbon($row->item_datepurchased);
      array_push($purchasesItems,
        array(
          'suppliers_name' => $supplier->supplier_name,
          'date_received' => $row->item_datereceived,
          'code' => $row->account->account_name,
          'po' => $row->item_purchaseorder_no,
          'pr' => $row->item_purchaserequest_no,
          'si' => $row->item_salesinvoice_no,
          'dr' => $row->item_deliveryreceipt_no,
          'particular' => $row->item_particular,
          'purchasedate' => $pdate->format('d/m/Y'),
          'duedate' => $pdate
            ->addDays($supplier->supplier_payment_terms)
            ->format('d/m/Y'),
          'tin' => $supplier->supplier_tin_number,
          'address' => $supplier->supplier_address,
          'amountphp' => $php,
          'amountusd' => $usd,
          'zerorated' => $zerorated,
        )
      );

      if($count - 1 == $key 
        || $data[$key + 1]->item_supplier_id !== $row->item_supplier_id)
      {
        array_push($purchasesItems,
          array(
            'suppliers_name' => $supplier->supplier_name." TOTAL",
            'date_received' => '',
            'code' => '',
            'po' => '',
            'pr' => '',
            'si' => '',
            'dr' => '',
            'particular' => '',
            'purchasedate' => '',
            'duedate' => '',
            'tin' => '',
            'address' => '',
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

    $company = $this->company;
    return view('purchasesms.PurchasesBirReport',
      compact('accountsTotal','purchasesItems','accountsTotalAll','fixedDate','company')
    );
  }
}
