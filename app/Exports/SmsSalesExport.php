<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesInvoice;

class SmsSalesExport implements FromView
{
  private $conversion;
  private $year;
  private $month;
  private $customer;

  public function __construct($conversion, $year, $month = -1, $customer = -1){
    $this->conversion = $conversion;
    $this->year = $year;
    $this->month = $month;
    $this->customer = $customer;
  }
    
  public function view(): View
  {
    $q = SalesInvoice::withTrashed()
      ->whereYear('s_deliverydate', $this->year);

      if($this->month !== -1) {
        $q->whereMonth('s_deliverydate', $this->month);
      }
      
      if($this->customer !== -1) {
        $q->where('s_customer_id', $this->customer);
      }

    $sales = $q->orderBy('s_invoicenum','desc')->get();

    return view('sales.exportSales', [
        'sales' => $sales,
        'conversion' => $this->conversion,
    ]);
  }
}
