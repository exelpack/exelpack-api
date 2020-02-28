<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

use App\SalesCustomer;
use App\SalesInvoice;
use App\SalesInvoiceItems;

use Carbon\Carbon;

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
        ->where('s_isRevised',0)
        ->whereNotBetween('s_deliverydate',
          [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
        ->where('s_customer_id',$this->cid);
        if(strtolower($this->currency) == 'php')
           $q->where('s_currency',$this->currency);
      })
      ->join('salesms_invoice as si','si.id','=','salesms_invoiceitems.sitem_sales_id')
      ->orderBy('si.s_deliverydate','ASC')
      ->get();  

      return view(strtolower($this->currency) == 'php' ? 'sales.exportSalesSOA' : 'sales.exportSalesSoaWithUsd', [
        'data' => $data,
        'currency' => $this->currency,
        'customer_id' => $this->cid,
        'customername' => $customer->c_customername,
        'pt' => $customer->c_paymentterms
      ]);

    }
}
