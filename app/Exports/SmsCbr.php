<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesInvoice;

class SmsCbr implements FromView
{
  private $month;
  private $year;

  public function __construct($month = 1, $year = 2020){
    $this->month = $month;
    $this->year = $year;
  }
    
  public function view(): View
  {
    $data = SalesInvoice::has('items')
      ->where('s_ornumber','!=',NULL)
      ->where('s_datecollected','!=',NULL)
      ->where('s_isRevised',0)
      ->whereMonth('s_deliverydate',$this->month)
      ->whereYear('s_deliverydate',$this->year)
      ->orderBy('s_datecollected','ASC')
      ->get()
      ->map(function ($sales){
        $total = $sales->items()->sum('sitem_totalamount');

        $phpwht = $total * ($sales->s_withholding / 100);
        $usdwht = 0;

        $collectedphp = $total - $phpwht;
        $collectedusd = 0;
        if($sales->s_currency === 'USD'){
          $phpwht = 0;
          $usdwht = $total * ($sales->s_withholding / 100);

          $collectedphp = 0;
          $collectedusd = $total - $usdwht;
        }

        return array(
          'id' => $sales->id,
          'dateCollected' => $sales->s_datecollected,
          'orNum' => $sales->s_ornumber,
          'customerName' => $sales->customer->c_customername,
          'invoiceNum' => $sales->s_invoicenum,
          'cwtUsd' => $usdwht == 0 ? '' : number_format($usdwht,2,'.',''),
          'cwtPhp' => $phpwht == 0 ? '' : number_format($phpwht,2,'.',''),
          'collectedUsd' => $collectedusd == 0 ? '' : number_format($collectedusd,2,'.',''),
          'collectedPhp' => $collectedphp == 0 ? '' : number_format($collectedphp,2,'.',''),
          'receivable' => $collectedphp == 0 
            ? number_format($collectedusd,2,'.','') 
            : number_format($collectedphp,2,'.',''),
        );
      })
      ->values();

    $month_word = array('January','February','March','April','May',
    'June','July','August','September','October','November','December');

    $m = $month_word[$this->month - 1];

    return view('sales.exportSalesCRB',  [
      'data' => $data,
      'm' => $m,
      'year' => $this->year,
    ]);

  }
}
