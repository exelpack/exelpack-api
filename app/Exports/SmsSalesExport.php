<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesInvoice;

class SmsSalesExport implements FromView
{
  private $conversion;

  public function __construct($conversion){
    $this->conversion = $conversion;
  }
    
  public function view(): View
  {
    $sales = SalesInvoice::withTrashed()
      ->orderBy('s_invoicenum','desc')->get();

    return view('sales.exportSales', [
        'sales' => $sales,
        'conversion' => $this->conversion
    ]);
  }
}
