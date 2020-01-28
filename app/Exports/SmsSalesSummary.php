<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\SalesInvoiceItems;

class SmsSalesSummary implements FromView
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
          $q->where('id','!=','NO CUSTOMER');
        })
        ->where('s.s_isRevised',0)
        ->orderBy('c.c_customername','ASC')
        ->orderBy('s.s_invoicenum','ASC')
        ->select('salesms_invoiceitems.*')
        ->get();

      $data = array();
      $total_usd = 0;
      $total_php = 0;
      $total_all = 0;
      $count = count($invoices);

      foreach($invoices as $key => $invoice){

        $usd = 0;
        $php = 0;
        $total_amt = 0;
        if($invoice->sales->s_currency === 'USD'){
            $usd = $invoice->sitem_totalamount;
            $total_amt = $invoice->sitem_totalamount * $conversion;
            $total_usd+= $usd;
            $total_all+= $total_amt;

        }else{
            $php = $invoice->sitem_totalamount;
            $total_amt = $invoice->sitem_totalamount;
            $total_php+= $php;
            $total_all+= $total_amt;
        }

        if($key === 0 && $count !== 1){

            array_push($data, array('company' => $invoice->sales->customer->c_customername,
               'delivery_date' => $invoice->sales->s_deliverydate,
               'invoice' => $invoice->sales->s_invoicenum,
               'dr_num' => $invoice->sitem_drnum,
               'po_num' => $invoice->sitem_ponum,
               'part_num' => $invoice->sitem_partnum,
               'usd' => "$ ".number_format($usd,2),
               'php' => "PHP ".number_format($php,2),
               'totalamount' => "PHP ".number_format($total_amt,2)));

        }
        ///if loop is in middle
        if($key !== 0 && $key != $count-1){
        //if same company to previous
          if($invoice->sales->customer->c_customername === $invoices[$key-1]->sales->customer->c_customername){

            array_push($data, array('company' => $invoice->sales->customer->c_customername,
               'delivery_date' => $invoice->sales->s_deliverydate,
               'invoice' => $invoice->sales->s_invoicenum,
               'dr_num' => $invoice->sitem_drnum,
               'po_num' => $invoice->sitem_ponum,
               'part_num' => $invoice->sitem_partnum,
               'usd' => "$ ".number_format($usd,2),
               'php' => "PHP ".number_format($php,2),
               'totalamount' => "PHP ".number_format($total_amt,2)));

          }else{//obviously if not
            $total_usd = doubleval($total_usd) - doubleval($usd);
            $total_php = doubleval($total_php) - doubleval($php);
            $total_all = doubleval($total_all) - doubleval($total_amt);
            array_push($data, array('company' => $invoices[$key-1]->sales->customer->c_customername." Total",
               'delivery_date' => '',
               'invoice' => '',
               'dr_num' => '',
               'po_num' => '',
               'part_num' => '',
               'usd' => "$ ". number_format($total_usd,2),
               'php' => "PHP ".number_format($total_php,2),
               'totalamount' => "PHP ".number_format($total_all,2) ));
            $total_usd = 0;
            $total_php = 0;
            $total_all = 0;
            array_push($data, array('company' => $invoice->sales->customer->c_customername,
               'delivery_date' => $invoice->sales->s_deliverydate,
               'invoice' => $invoice->sales->s_invoicenum,
               'dr_num' => $invoice->sitem_drnum,
               'po_num' => $invoice->sitem_ponum,
               'part_num' => $invoice->sitem_partnum,
               'usd' => "$ ". number_format($usd,2),
               'php' => "PHP ".number_format($php,2),
               'totalamount' => "PHP ".number_format($total_amt,2)) );

            $total_all+= $total_amt;
            $total_php+= $php;
            $total_usd+= $usd;
          }

        }

        //if last
        if($count-1 === $key){

          if($count === 1) {
             array_push($data, array('company' => $invoice->sales->customer->c_customername,
                 'delivery_date' => $invoice->sales->s_deliverydate,
                 'invoice' => $invoice->sales->s_invoicenum,
                 'dr_num' => $invoice->sitem_drnum,
                 'po_num' => $invoice->sitem_ponum,
                 'part_num' => $invoice->sitem_partnum,
                 'usd' => "$ ".number_format($usd,2),
                 'php' => "PHP ".number_format($php,2),
                 'totalamount' => "PHP ".number_format($total_amt,2)) );

              array_push($data, array('company' => $invoice->sales->customer->c_customername." Total",
                 'delivery_date' => '',
                 'invoice' => '',
                 'dr_num' => '',
                 'po_num' => '',
                 'part_num' => '',
                 'usd' => "$ ".number_format($total_usd,2),
                 'php' => "PHP ".number_format($total_php,2),
                 'totalamount' => "PHP ".number_format($total_all,2) ));
              $total_usd = 0;
              $total_php = 0;
              $total_all = 0;
          }else{
            array_push($data, array('company' => $invoice->sales->customer->c_customername,
               'delivery_date' => $invoice->sales->s_deliverydate,
               'invoice' => $invoice->sales->s_invoicenum,
               'dr_num' => $invoice->sitem_drnum,
               'po_num' => $invoice->sitem_ponum,
               'part_num' => $invoice->sitem_partnum,
               'usd' => "$ ".number_format($usd,2),
               'php' => "PHP ".number_format($php,2),
               'totalamount' => "PHP ".number_format($total_amt,2)));

            array_push($data, array('company' => $invoice->sales->customer->c_customername." Total",
               'delivery_date' => '',
               'invoice' => '',
               'dr_num' => '',
               'po_num' => '',
               'part_num' => '',
               'usd' => "$ ".number_format($total_usd,2),
               'php' => "PHP ".number_format($total_php,2),
               'totalamount' => "PHP ".number_format($total_all,2) ) );
            $total_usd = 0;
            $total_php = 0;
            $total_all = 0;
          }

        }

      }                          
      $month_word = array('January','February','March','April','May',
        'June','July','August','September','October','November','December');

      $m = $month_word[$month - 1];

      return view('sales.exportSalesSummary', [
        'data' => $data,
        'm' => $m
      ]);
  
    }
}
