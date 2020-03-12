<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesInvoice;

class SmsSalesExport implements FromView
{
  private $conversion;
  private $year;

  public function __construct($conversion, $year){
    $this->conversion = $conversion;
    $this->year = $year;
  }
    
  public function view(): View
  {
    $sales = SalesInvoice::withTrashed()
      ->whereYear('s_deliverydate', $this->year)
      ->orderBy('s_invoicenum','desc')->get();

    return view('sales.exportSales', [
        'sales' => $sales,
        'conversion' => $this->conversion,
    ]);
  }
}
