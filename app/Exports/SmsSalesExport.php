<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesInvoiceItems;

class SmsSalesExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
      $sales = SalesInvoiceItems::all();
      $conversion = 50;

      return view('sales.exportSales', [
          'sales' => $sales,
          'conversion' => $conversion
      ]);
    }
}
