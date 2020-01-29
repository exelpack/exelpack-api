<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

use App\SalesCustomer;
use App\SalesInvoice;
use App\SalesInvoiceItems;

use App\Exports\SmsSalesExport;
use App\Exports\SmsSalesSummary;
use App\Exports\SmsSalesSummaryExternal;
use App\Exports\SmsSOA;

class SalesController extends Controller
{

  public function exportAR(){

    if(!request()->has('month') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        ['Month, year & conversion parameters are required.']
      ],422); 
    }
    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;

    $sales=  SalesInvoice::whereHas('customer', function($q){
        $q->where('c_customername','NOT LIKE','%NO CUSTOMER%');
      })->groupBy('s_customer_id','s_currency')
      ->get();
    $customers = array();
    $amounts = array();
   

    foreach($sales as $row){
      array_push($customers,array('customer' => $row->customer->c_customername, 
          'customer_id' => $row->s_customer_id,
          'currency' => $row->s_currency,
          'payment_terms' => $row->customer->c_paymentterms));
    }

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
    return $customers;
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

          $acct_receivable = SalesItems::whereHas('sales',function ($q) use($row){
              $q->where('currency',$row['currency'])
              ->where('customer_id',$row['customer_id']);
          })
          ->get();

            foreach($acct_receivable as $key => $data)
            {   
                //declare object
                $ar = $data->sales->ar;
                $sales = $data->sales;
                //delivered
                $delivered = new Carbon($sales->delivery_date);
                $diff = $delivered->diffInDays($_endDate->format('Y-m-d'));
                $due = $delivered->addDays($row['payment_terms']);
                // $_date_col = new Carbon($data->date_collected);

                if($ar)
                    $_date_col = new Carbon($ar->date_collected);
                else{
                    $_date_col = Carbon::now();
                    $_date_col->subDays(365);
                }

                //check if date collected is this month
                if($_date_col->format('Y-m') == $_endDate->format('Y-m')){
                    $overall_receivable+= $data->total_amount;

                    if($ar){
                        $percent = (100 - $ar->withholdingtax) / 100;
                        $amt_collected+= $data->total_amount * $percent;
                    }

                    if($_endDate->format('Y-m-d') >= $due->format('Y-m-d')){
                        $total_collectibles_month+= $data->total_amount; 
                    }
                }else{
                    if(!$ar){
                        $overall_receivable+= $data->total_amount;

                        if($_endDate->format('Y-m-d') >= $due->format('Y-m-d')){

                            $total_collectibles_month+= $data->total_amount;
                        }

                    }else{
                        continue;
                    }
                }

                if($diff > 365){
                    $aging_six+= $data->total_amount;
                }
                else if($diff > 119){
                    $aging_fifth+= $data->total_amount;
                }
                else if($diff > 89){
                    $aging_fourth+= $data->total_amount; 
                }
                else if($diff > 59){
                    $aging_third+= $data->total_amount; 
                }
                else if($diff > 29){
                    $aging_second+= $data->total_amount; 
                }
                else if($diff < 30){
                    $aging_first+= $data->total_amount;
                }

            }

            $crcy = $row['currency'] === 'USD' ? '$' : '';
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
                    'overall' => $overall_receivable == 0 ? '' : $crcy.' '.number_format($overall_receivable,2),
                    'aging_first' => $aging_first == 0 ? '' : $crcy.' '.number_format($aging_first,2),
                    'aging_second' => $aging_second == 0 ? '' : $crcy.' '.number_format($aging_second,2),
                    'aging_third' => $aging_third == 0 ? '' : $crcy.' '.number_format($aging_third,2),
                    'aging_fourth' => $aging_fourth == 0 ? '' : $crcy.' '.number_format($aging_fourth,2),
                    'aging_fifth' => $aging_fifth == 0 ? '' : $crcy.' '.number_format($aging_fifth,2),
                    'aging_six' => $aging_six == 0 ? '' : $crcy.' '.number_format($aging_six,2),
                    'total_collectibles_month' => $total_collectibles_month == 0 
                    ? '' : $crcy.' '.number_format($total_collectibles_month,2),
                    'total_remaining_balance' => $remaining <= 0 
                    ? '' : $crcy.' '.number_format($remaining,2),
                    'amt_collected'  => $amt_collected == 0 ? '' : $crcy.' '.number_format($amt_collected,2),
                    'perc_collected' => number_format($perc_collected,2)."%"
                    // 'tester' => $tester
                )
            );

        }//loop on customer by currency

        //get collected dates on selected date
        $collected_dates = AccountsReceivable::select('date_collected')
        ->whereRaw('extract(month from date_collected) = ?',array($month))
        ->whereRaw('extract(year from date_collected) = ?',array($year))
        ->groupBy('date_collected')
        ->orderBy('date_collected','asc')
        ->get();

  }

  public function exportSales()
  {
    return Excel::download(new SmsSalesExport, 'sales.xlsx');
  }

  public function exportSalesSummary()
  {

    if(!request()->has('month') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        ['Month, year & conversion parameters are required.']
      ],422); 
    }
    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;

    return Excel::download(new SmsSalesSummary($month,$year,$conversion), 'salesSummary.xlsx');
  }

  public function exportSalesSummaryExternal()
  {

    if(!request()->has('month') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        ['Month, year & conversion parameters are required.']
      ],422); 
    }
    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;

    return Excel::download(new SmsSalesSummaryExternal($month,$year,$conversion), 'salesSummaryExternal.xlsx');
  }

  public function exportSoa()
  {

    if(!request()->has('cid') || !request()->has('currency')){
      return response()->json([
        ['Customer ID and currency parameters are required.']
      ],422); 
    }

    $cid = request()->cid;
    $currency = request()->currency;

    return Excel::download(new SmsSOA($cid,$currency), 'SmsSOA.xlsx');
  }

  public function test()
  {

    $items = DB::table('test_table')->get()
              ->map(function ($item) {

                $si = SalesInvoice::where('s_invoicenum',$item->si)->first();
                $i = array(
                  'sitem_sales_id' => $si->id,
                  'sitem_drnum' => $item->dr,
                  'sitem_ponum' => $item->po,
                  'sitem_partnum' => $item->pn == '' ? 'NA' : $item->pn,
                  'sitem_quantity' => $item->qty,
                  'sitem_unitprice' => $item->un,
                  'sitem_totalamount' => doubleval($item->un) 
                    * intval($item->qty),
                );

                SalesInvoiceItems::create($i);
                return $i;
              });
    return $items;

    // $sales = DB::table('salesms_invoicecopy')
    //           ->groupBy('s_invoicenum')
    //           ->get()
    //           ->map(function ($invoice){

    //             $wht = $invoice->s_withholding;
    //             $or = trim($invoice->s_ornumber);
    //             $date = $invoice->s_datecollected;
    //             $deleted = null;
    //             $remarks = $invoice->s_remarks;

    //             if($invoice->s_withholding == 0)
    //               $wht = null;

    //             if($date == "0000-00-00"){
    //               $wht = null;
    //               $or = null;
    //               $date = null;
    //             }

    //             if($invoice->s_remarks == 'cancelled')
    //               $deleted = date('Y-m-d H:i:s');

    //             if($remarks == ""){
    //               $remarks =  null;
    //             }

    //             $s = array(
    //               's_customer_id' => $invoice->s_customer_id,
    //               's_invoicenum' => trim($invoice->s_invoicenum),
    //               's_deliverydate' => $invoice->s_deliverydate,
    //               's_currency' => trim($invoice->s_currency),
    //               's_ornumber' => $or,
    //               's_datecollected' => $date,
    //               's_withholding' => $wht,
    //               's_remarks' => trim($invoice->s_remarks),
    //               's_isRevised' => $invoice->s_isRevised,
    //               'deleted_at' => $deleted
    //             );
    //             // SalesInvoice::insert($s);   
    //             return $s;


    //           })
    //           ->toArray();
    // return $sales;
    //   return count($sales);
  }

  private $salesRules = array();

  public function __construct(){
    $this->salesRules = array(
      'customer' => 'integer|required',
      'delivery_date' => 'date|required|before_or_equal:'.date('Y-m-d'),
      'currency' => 'string|max:5|in:PHP,USD|required',
      'invoiceItems' => 'array|min:1|required',
      'or_num' => 'sometimes|required_if:markAsPaid,true',
      'date_collected' => 'sometimes|required_if:markAsPaid,true|nullable',
      'withholdingtax' => 'sometimes|integer|nullable|min:0',
    );
  }

	public function getCustomers()
	{

		$customers = SalesCustomer::all()
    		->map(function($customer) {
    			return array(
    				'id' => $customer->id,
    				'customer_name' => $customer->c_customername,
    			);
    		});

		return response()->json(
			[
				'customersList' => $customers
			]);

	}
  
	public function getSales()
	{
    $pageSize = request()->pageSize;

    $q = SalesInvoice::query();
    
    if(request()->has('showRecord')){
      $showRecord = request()->showRecord;
      if($showRecord == 'All'){
        $q->withTrashed();
      }else if($showRecord == 'Collected'){
        $q->where('s_ornumber','!=',NULL)
          ->where('s_datecollected','!=',NULL);
      }else if($showRecord == 'NotCollected'){
        $q->where('s_ornumber',NULL)
          ->where('s_datecollected',NULL);
      }else if($showRecord == 'Revised'){
        $q->where('s_isRevised',1);
      }else if($showRecord == 'Cancelled'){
        $q->onlyTrashed();
      }

    }

    if(request()->has('currency')){
      $currency = request()->currency;

      if($currency !== 'All')
        $q->where('s_currency',$currency);
    }

    if(request()->has('month')){
      $q->whereMonth('s_deliverydate',request()->month);
    }

    if(request()->has('year')){
      $q->whereYear('s_customer_id',request()->year);
    }

    if(request()->has('customer')){
      $q->where('s_customer_id',request()->customer);
    }

    if(request()->has('search')){
      $q->where('s_invoicenum','LIKE','%'.request()->search.'%');
    }
    
    if(request()->has('sort')){
      $sort = request()->sort;

      if($sort == 'invoiceDesc')
        $q->orderBy('s_invoicenum','DESC');
      else if($sort == 'invoiceAsc')
        $q->orderBy('s_invoicenum','ASC');
      else if($sort == 'dateAsc')
        $q->orderBy('s_deliverydate','ASC');
      else if($sort == 'dateDesc')
        $q->orderBy('s_deliverydate','DESC');

    }

    $salesRes = $q->paginate($pageSize);

    $sales = $salesRes->map(function($invoice){ 
         return $this->invoiceGetArray($invoice);
      })->toArray();


  return response()->json(
  	[
      'salesListLength' => $salesRes->total(),
  		'salesList' => $sales,
      
  	]);

	}

  public function createSales(Request $request){

    $validator = Validator::make($request->all(),
      array_merge(['invoice_num' => 
        'string|max:50|required|unique:salesms_invoice,s_invoicenum']
        ,$this->salesRules));

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $sales = new SalesInvoice();
    $sales->fill($this->invoiceInputArray($request));
    $sales->save();

    foreach($request->invoiceItems as $item){
      $sales->items()->create($this->invoiceInputItemArray($item));
    }

    return response()->json(
      [
        'newSales' => $this->invoiceGetArray($sales),
        'message' => 'Record created'
      ]);

  }

  public function updateSales(Request $request,$id){
    $validator = Validator::make($request->all(),
      array_merge(['invoice_num' => 
        'string|max:50|required|unique:salesms_invoice,s_invoicenum,'.$id]
        ,$this->salesRules));

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $sales = SalesInvoice::findOrFail($id);
    $sales->fill($this->invoiceInputArray($request));

    if($sales->isDirty()){
      $sales->save();
    }

    $invoiceItemsId = array_column($request->invoiceItems,'id'); //get request items id

    foreach($sales->items as $item){
      if(!in_array($item->id,$invoiceItemsId)){ // delete item if didnt exist anymore on edited invoice
        $sales->items()->find($item['id'])->delete();
        // $this->logCreateDeletePrItem($pr->pr_prnum,$item['pri_code'],"Deleted");
      }
    }

    foreach($request->invoiceItems as $item){ //adding and editing item
      if(isset($item['id'])){ //check if item exists on invoice alr then update
        $salesitem = $sales->items()->find($item['id'])->fill($this->invoiceInputItemArray($item));

        if($salesitem->isDirty()){
          // $this->logPrItemEdit($pritem->getDirty(),$pritem->getOriginal(),
          //   $pritem->pr->pr_prnum,$pritem->pri_code);
          $salesitem->save();
        }
      }else{
        //if item doesnt exist on po then add.
        $salesitem = $sales->items()->create($this->invoiceInputItemArray($item));
        // $this->logCreateDeletePrItem($pr->pr_prnum,$pritem->pri_code,"Added");
      }
    }
    $sales->refresh();

    return response()->json(
      [
        'updatedSales' => $this->invoiceGetArray($sales),
        'message' => 'Record updated'
      ]);

  }

  public function deleteSales($id)
  {

    $sales = SalesInvoice::withTrashed()->findOrFail($id);
    $invoice = $sales->s_invoicenum;

    if($sales->deleted_at){
      $sales->restore();
    }else
      $sales->delete();
 
    $sales->refresh();

    return response()->json(
      [
        'updatedSales' => $this->invoiceGetArray($sales),
        'message' => 'Record updated'
      ]);

  } 

  public function reviseSales($id)
  {

    $sales = SalesInvoice::withTrashed()->findOrFail($id);
    $invoice = $sales->s_invoicenum;

    $isRev = 1;
    if($sales->s_isRevised){
      $isRev = 0;
    }

    $sales->update([
      's_isRevised' => $isRev
    ]);
 
    $sales->refresh();

    return response()->json(
      [
        'updatedSales' => $this->invoiceGetArray($sales),
        'message' => 'Record updated'
      ]);

  }

  public function markInvoicesCollected(Request $request)
  {

    $validator = Validator::make($request->all(),[
      'invoiceKeys' => 'array|min:1|required',
      'or_num' => 'required_if:markAsPaid,true',
      'date_collected' => 'required_if:markAsPaid,true|nullable',
      'withholdingtax' => 'integer|nullable|min:0',
    ],[],
    [
      'invoiceKeys' => 'Invoice id'
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    SalesInvoice::whereIn('id',$request->invoiceKeys)->update([
      's_ornumber' => $request->or_num,
      's_datecollected' => $request->date_collected,
      's_withholding' => $request->withholdingtax,
    ]);

    $sales = SalesInvoice::whereIn('id',$request->invoiceKeys)
      ->get()
      ->map(function($invoice){  
         return $this->invoiceGetArray($invoice);
      })->toArray();

      return response()->json(
      [
        'salesList' => $sales,
        'message' => 'Record/s successfully updated as collected'
      ]);

  }

  public function invoiceGetArray($invoice){
    $status = 'Not Collected';
    $wht = $invoice->s_withholding;

    if($invoice->s_ornumber && $invoice->s_datecollected)
      $status = 'Collected';

    if($invoice->deleted_at)
      $status = 'Cancelled';

    if($invoice->s_isRevised)
      $status = 'Revised';

    if($wht)
      $wht = $wht / 100 .'%';

    return array(
      'id' => $invoice->id,
      'status' => $status,
      'invoice_num' => $invoice->s_invoicenum,
      'delivery_date' => $invoice->s_deliverydate,
      'currency' => $invoice->s_currency,
      'customer' => $invoice->customer->id,
      'customer_name' => $invoice->customer->c_customername,
      'payment_terms' => $invoice->customer->c_paymentterms,
      'withholdingtax' => $wht,
      'or_num' => $invoice->s_ornumber,
      'date_collected' => $invoice->s_datecollected,
      'isRevised' => $invoice->s_isRevised,
      'itemCount' => $invoice->items()->count(),
      'totalAmount' => number_format($invoice->items()->sum('sitem_totalamount'),2,'.',''),
      'invoiceItems' => $invoice->items->map(function($item) {
          return $this->invoiceItemGetArray($item);
        })
      );
  }

  public function invoiceItemGetArray($item){

    return array(
      'id' => $item->id,
      'dr' => $item->sitem_drnum,
      'po' => $item->sitem_ponum,
      'partnum' => $item->sitem_partnum,
      'quantity' => $item->sitem_quantity,
      'unitprice' => $item->sitem_unitprice,
      'remarks' => $item->sitem_remarks,
      'totalAmount' => $item->sitem_totalamount,
    );
  }

  public function invoiceInputArray($invoice){
    return array(
      's_customer_id' => $invoice->customer,
      's_invoicenum' => $invoice->invoice_num,
      's_deliverydate' => $invoice->delivery_date,
      's_currency' => $invoice->currency,
      's_ornumber' => $invoice->or_num,
      's_datecollected' => $invoice->date_collected,
      's_withholding' => $invoice->withholdingtax,
    );
  }

  public function invoiceInputItemArray($item){
    return array(
      'sitem_drnum' => $item['dr'],
      'sitem_ponum' => $item['po'],
      'sitem_partnum' => $item['partnum'] == '' ? 'NA' : $item['partnum'],
      'sitem_quantity' => $item['quantity'],
      'sitem_unitprice' => number_format($item['unitprice'],2),
      'sitem_totalamount' => doubleval($item['unitprice']) 
        * intval($item['quantity']),
    );
  }


}
