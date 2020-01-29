<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesCustomer;
use App\SalesInvoice;
use App\SalesInvoiceItems;

class SmsSOA implements FromView
{
    private $cid = '';
    private $currency = '';

    public function __construct($cid = 1,$currency = 'PHP'){
      $this->cid = $cid;
      $this->currency = $currency;
    }

    public function view() :View
    {
      
      $customer = SalesCustomer::findOrFail($this->cid);
      $data = SalesInvoiceItems::whereHas('sales', function($q) {
        $q->where('s_ornumber','=',NULL)
        ->where('s_datecollected','=',NULL)
        ->where('s_customer_id',$this->cid);

        if(strtolower($this->currency) == 'PHP')
           $q->where('s_currency',$this->currency);

      })
      ->get();  

      return view($this->currency == 'PHP' ? 'sales.exportSalesSOA' : 'sales.exportSalesSoaWithUsd', [
        'data' => $data,
        'currency' => $this->currency,
        'customer_id' => $this->cid,
        'customername' => $customer->c_customername,
        'pt' => $customer->c_paymentterms
      ]);

    }
}
