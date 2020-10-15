<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\SalesInvoice;
use Carbon\Carbon;
use DB;

class SmsSalesWeeklyAR implements FromView
{
		private $week;
    private $year;
    private $conversion;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct($week = 1,$year = 2020,$conversion = 50){
      $this->week = $week;
      $this->year = $year;
      $this->conversion = $conversion;
    }

    public function view() : View
    {

    	$week = $this->week;
	    $year = $this->year;
	    $conversion = $this->conversion;

	    $weekDate = Carbon::now()->setISODate($year,$week);
	    $weekStartDate = $weekDate->startOfWeek()->format('Y-m-d');
	    $weekEndDate = $weekDate->endOfWeek()->format('Y-m-d');

	    $sales =  SalesInvoice::whereHas('customer', function($q){
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

	    foreach($customers as $row) {

	      $sales = SalesInvoice::where('s_currency',$row['currency'])
	          ->where('s_customer_id',$row['customer_id'])
	          ->whereNotBetween('s_deliverydate',
	            [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
	          ->where('s_isRevised',0)
	          ->where('s_ornumber','=',NULL)
		        ->where('s_datecollected','=',NULL)
	          ->get();

	      $due = $sales->filter(function ($val) use ($weekEndDate) {
	          $now = Carbon::now()->tomorrow()->toDateTimeString();
	          $pt = $val->customer->c_paymentterms;
	          $deliverydate = Carbon::parse($val->s_deliverydate);
	          $dueDate = $deliverydate->addDays($pt);
	          return $dueDate->between($now,$weekEndDate);
	        })
	        ->map(function($inv) use ($conversion) {

	          $total = $inv->items()->sum(Db::raw('sitem_quantity * sitem_unitprice'));
	          return array(
	            'total' => $total
	          );
	        })
	        ->sum('total');


	      $overdue = $sales->filter(function ($val) use ($weekEndDate) {
	          $now = Carbon::now()->toDateTimeString();
	          $pt = $val->customer->c_paymentterms;
	          $deliverydate = Carbon::parse($val->s_deliverydate);
	          $dueDate = $deliverydate->addDays($pt);

	          $lastday_2017 = Carbon::parse('2017-12-31')->toDateTimeString();
	          return $dueDate->between($lastday_2017,$now);
	        })
	        ->map(function($inv) use ($conversion) {

	          $total = $inv->items()->sum(Db::raw('sitem_quantity * sitem_unitprice'));
	          return array(
	            'total' => $total
	          );
	        })
	        ->sum('total');

	      $overdue = $sales->filter(function ($val) {
	          $now = Carbon::now()->toDateTimeString();
	          $pt = $val->customer->c_paymentterms;
	          $deliverydate = Carbon::parse($val->s_deliverydate);
	          $dueDate = $deliverydate->addDays($pt);

	          $first_day2017 = Carbon::parse('2017-01-01')->toDateTimeString();
	          return $dueDate->between($first_day2017,$now);
	        })
	        ->map(function($inv) use ($conversion) {

	          $total = $inv->items()->sum(Db::raw('sitem_quantity * sitem_unitprice'));
	          return array(
	            'total' => $total
	          );
	        })
	        ->sum('total');

	      $delinquent = $sales->filter(function ($val) {
	          $pt = $val->customer->c_paymentterms;
	          $deliverydate = Carbon::parse($val->s_deliverydate);
	          $dueDate = $deliverydate->addDays($pt);

	          $lastday_2016 = Carbon::parse('2016-12-31')->toDateTimeString();
	          return $dueDate->lessThanOrEqualTo($lastday_2016);
	        })
	        ->map(function($inv) use ($conversion) {

	          $total = $inv->items()->sum(Db::raw('sitem_quantity * sitem_unitprice'));
	          return array(
	            'total' => $total
	          );
	        })
	        ->sum('total');

	      array_push($amounts, array(
	        'customer' => $row['customer']." ".$row['currency'],
	        'terms' => $row['payment_terms'],
	        'currency' => $row['currency'],
	        'due' => $due,
	        'overdue' => $overdue,
	        'delinquent' => $delinquent,
	        'collectibles' => $due + $overdue + $delinquent,
	      ));
	    }
	    $title = 'Report for week '.$week;
	    $collect_amounts = collect($amounts);
	    $amounts_in_php = $collect_amounts->filter(function ($val) {
	        return $val['currency'] === "PHP";
	      });
	    $amounts_in_usd = $collect_amounts->filter(function ($val) {
	        return $val['currency'] === "USD";
	      });

	    $totalPhp = array(
	      'due' => $amounts_in_php->sum('due'),
	      'overdue' => $amounts_in_php->sum('overdue'),
	      'delinquent' => $amounts_in_php->sum('delinquent'),
	      'collectibles' => $amounts_in_php->sum('collectibles'),
	    );

	    $totalUsd = array(
	      'due' => $amounts_in_usd->sum('due'),
	      'overdue' => $amounts_in_usd->sum('overdue'),
	      'delinquent' => $amounts_in_usd->sum('delinquent'),
	      'collectibles' => $amounts_in_usd->sum('collectibles'),
	    );

	    return view('sales.exportArWeekly', compact('title','amounts', 'totalPhp', 'totalUsd'));
    }
}
