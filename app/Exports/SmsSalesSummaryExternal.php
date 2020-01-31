<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\SalesInvoiceItems;

class SmsSalesSummaryExternal implements FromView
{
    private $month = '';
    private $year = '';
    private $conversion = '';

    public function __construct($month = 1,$year = 2020,$conversion = 50){
      $this->month = $month;
      $this->year = $year;
      $this->conversion = $conversion;
    }
  
    public function view(): View
    {
      $month = $this->month;
      $year = $this->year ;
      $conversion = $this->conversion;
      $invoices = SalesInvoiceItems::join('salesms_invoice as s','s.id','=',                            
          'salesms_invoiceitems.sitem_sales_id')
        ->leftJoin('salesms_customers as c','c.id','s.s_customer_id')
        ->whereMonth('s.s_deliverydate',$month)
        ->whereYear('s.s_deliverydate',$year)
        ->whereHas('sales.customer', function($q){
          $q->where('c_customername','NOT LIKE','%NO CUSTOMER%');
        })
        ->where('s.s_isRevised',0)
        ->orderBy('c.c_customername','ASC')
        ->orderBy('s.s_invoicenum','ASC')
        ->select('salesms_invoiceitems.*')
        ->get();

      $month_word = array('January','February','March','April','May',
      'June','July','August','September','October','November','December');

      $m = $month_word[$month - 1];

      return view('sales.exportSalesSummaryExternal', [
        'sales' => $invoices,
        'm' => $m,
        'conversion' => $conversion,
      ]);
    }
}
