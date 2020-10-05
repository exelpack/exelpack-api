<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\SalesInvoice;
use App\SalesInvoiceItems;
use Carbon\Carbon;

class SmsAR implements FromView
{
    private $month = '';
    private $year = '';
    private $conversion = '';

    public function __construct($month = 1,$year = date('Y'),$conversion = 50){
      $this->month = $month;
      $this->year = $year;
      $this->conversion = $conversion;
    }

    public function view() : View
    {
      $sales=  SalesInvoice::whereHas('customer', function($q){
        $q->where('c_customername','NOT LIKE','%NO CUSTOMER%');
      })
      ->groupBy('s_customer_id','s_currency')
      ->get();
      $customers = array();
      $amounts = array();

      foreach($sales as $row){
        array_push($customers,array('customer' => $row->customer->c_customername, 
          'customer_id' => $row->s_customer_id,
          'currency' => $row->s_currency,
          'payment_terms' => $row->customer->c_paymentterms));
      }

      $year = $this->year;
      $month = $this->month;
      $conversion = $this->conversion;

      $_endDate = Carbon::createFromDate($year,$month)->endOfMonth();

      //totals
      $total_overall_php = 0;
      $total_overall_usd = 0;
      $aging_first_php = 0;
      $aging_first_usd = 0;
      $aging_second_php = 0;
      $aging_second_usd = 0;
      $aging_third_php = 0;
      $aging_third_usd = 0;
      $aging_fourth_php = 0;
      $aging_fourth_usd = 0;
      $aging_fifth_php = 0;
      $aging_fifth_usd = 0;
      $aging_six_php = 0;
      $aging_six_usd = 0;
      $receivable_of_month_php = 0;
      $receivable_of_month_usd = 0;
      $total_remaining_php = 0;
      $total_remaining_usd = 0;
      $total_collected_php = 0;
      $total_collected_usd = 0;
      foreach($customers as $row){

        $aging_first = 0;
        $aging_second = 0;
        $aging_third = 0;
        $aging_fourth = 0;
        $aging_fifth = 0;
        $aging_six = 0;

        $overall_receivable = 0;
        $total_collectibles_month = 0;

        //
        $date_collected = 0;
        $amt_collected = 0;

        $acct_receivable = SalesInvoiceItems::whereHas('sales',function ($q) use($row){
            $q->where('s_currency',$row['currency'])
            ->where('s_customer_id',$row['customer_id'])
            ->whereNotBetween('s_deliverydate',
            [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->where('s_isRevised',0);
          })
          ->get();
          foreach($acct_receivable as $key => $data)
          {   
            $sales = $data->sales;
            //delivered
            $delivered = new Carbon($sales->s_deliverydate);
            $diff = $delivered->diffInDays($_endDate->format('Y-m-d'));
            //declare object$_endDate->format('Y-m-d'));
            $due = $delivered->addDays($row['payment_terms']);
            // $_date_col = new Carbon($data->date_collected);

            if($sales->s_datecollected != null && $sales->s_ornumber != null)
              $_date_col = new Carbon($sales->s_datecollected);
            else{
              $_date_col = Carbon::now();
              $_date_col->subDays(365);
            }

            //check if date collected is this month
            if($_date_col->format('Y-m') == $_endDate->format('Y-m')){
              $overall_receivable+= $data->sitem_totalamount;

              if($sales->s_datecollected != null && $sales->s_ornumber != null){
                $percent = (100 - $sales->s_withholding) / 100;
                $amt_collected+= $data->sitem_totalamount * $percent;
              }

              if($_endDate->format('Y-m-d') >= $due->format('Y-m-d')){
                $total_collectibles_month+= $data->sitem_totalamount; 
              }
            }else{
              if($sales->s_datecollected == null && $sales->s_ornumber == null){
                $overall_receivable+= $data->sitem_totalamount;

                if($_endDate->format('Y-m-d') >= $due->format('Y-m-d')){

                  $total_collectibles_month+= $data->sitem_totalamount;
                }

              }else{
                continue;
              }
            }

            if($diff > 365){
                $aging_six+= $data->sitem_totalamount;
            }
            else if($diff > 119){
                $aging_fifth+= $data->sitem_totalamount;
            }
            else if($diff > 89){
                $aging_fourth+= $data->sitem_totalamount; 
            }
            else if($diff > 59){
                $aging_third+= $data->sitem_totalamount; 
            }
            else if($diff > 29){
                $aging_second+= $data->sitem_totalamount; 
            }
            else if($diff < 30){
                $aging_first+= $data->sitem_totalamount;
            }

          }

          $remaining = $total_collectibles_month  - $amt_collected;

          if($amt_collected === 0 || $total_collectibles_month === 0){
              $perc_collected = 0;
          }else
          $perc_collected = ($amt_collected / $total_collectibles_month)  * 100;


          if($row['currency'] === 'PHP'){
              $total_overall_php+=$overall_receivable;
              $aging_first_php+= $aging_first;
              $aging_second_php+= $aging_second;
              $aging_third_php+= $aging_third;
              $aging_fourth_php+= $aging_fourth;
              $aging_fifth_php+= $aging_fifth;
              $aging_six_php+= $aging_six;
              $receivable_of_month_php+= $total_collectibles_month;
              $total_remaining_php+= $remaining;   
              $total_collected_php+= $amt_collected;
          }else{
              $total_overall_usd+=$overall_receivable;
              $aging_first_usd+= $aging_first;
              $aging_second_usd+= $aging_second;
              $aging_third_usd+= $aging_third;
              $aging_fourth_usd+= $aging_fourth;
              $aging_fifth_usd+= $aging_fifth;
              $aging_six_usd+= $aging_six;
              $receivable_of_month_usd+= $total_collectibles_month;
              $total_remaining_usd+= $remaining;
              $total_collected_usd+= $amt_collected;
          }
        
          array_push($amounts,
            array(
              'c_id' => $row['customer_id'],
              'companyname' => $row['customer'] ,
              'currency' => $row['currency'] ,
              'payment_terms' => $row['payment_terms'],
              'overall' => $overall_receivable == 0 ? '' : number_format($overall_receivable,4),
              'aging_first' => $aging_first == 0 ? '' : number_format($aging_first,4),
              'aging_second' => $aging_second == 0 ? '' : number_format($aging_second,4),
              'aging_third' => $aging_third == 0 ? '' : number_format($aging_third,4),
              'aging_fourth' => $aging_fourth == 0 ? '' : number_format($aging_fourth,4),
              'aging_fifth' => $aging_fifth == 0 ? '' : number_format($aging_fifth,4),
              'aging_six' => $aging_six == 0 ? '' : number_format($aging_six,4),
              'total_collectibles_month' => $total_collectibles_month == 0 
              ? '' : number_format($total_collectibles_month,4),
              'total_remaining_balance' => $remaining <= 0 
              ? '' : number_format($remaining,4),
              'amt_collected'  => $amt_collected == 0 ? '' : number_format($amt_collected,4),
              'perc_collected' => number_format($perc_collected,4)."%"
            )
          );


      }//loop on customer by currency

    //get collected dates on selected date
    $collected_dates = SalesInvoice::select('s_datecollected')
      ->whereRaw('extract(month from s_datecollected) = ?',array($month))
      ->whereRaw('extract(year from s_datecollected) = ?',array($year))
      ->where('s_ornumber','!=',NULL)
      ->groupBy('s_datecollected')
      ->orderBy('s_datecollected','asc')
      ->pluck('s_datecollected')
      ->toArray();

     return view('sales.exportAr',
      compact('amounts',
          'collected_dates',
          'month',
          'year',
          'total_overall_php',
          'total_overall_usd',
          'aging_first_php',
          'aging_first_usd',
          'aging_second_php',
          'aging_second_usd',
          'aging_third_php',
          'aging_third_usd',
          'aging_fourth_php',
          'aging_fourth_usd',
          'aging_fifth_php',
          'aging_fifth_usd',
          'aging_six_php',
          'aging_six_usd',
          'receivable_of_month_php',
          'receivable_of_month_usd',
          'total_remaining_php',
          'total_remaining_usd',
          'total_collected_php',
          'total_collected_usd',
          'conversion'
      ));
  }
} 
