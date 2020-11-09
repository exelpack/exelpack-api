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
use App\Exports\SmsAR;
use App\Exports\SmsCbr;
use App\Exports\SmsSalesWeeklyAR;

class SalesController extends Controller
{
  public function exportCRB(){

    if(!request()->has('month') || !request()->has('year')){
      return response()->json([
        'errors' => ['Month & year parameters are required.']
      ],422);
    }
    $month = request()->month;
    $year = request()->year;

    return Excel::download(new SmsCbr($month,$year), 'salescrb.xlsx');
  }

  public function exportAR(){

    if(!request()->has('month') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        'errors' => ['Month, year & conversion parameters are required.']
      ],422); 
    }
    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;

    return Excel::download(new SmsAR($month,$year,$conversion), 'salesaR.xlsx');
  }

  public static function getCollectedAmount($date,$company,$currency)
  {
    $sales = SalesInvoice::where('s_customer_id',$company)
    ->where('s_currency',$currency)
    ->where('s_datecollected',$date)
    ->get();

    $total_amt = 0;
    foreach($sales as $row)
    {
      $percent = (100 - $row->s_withholding) / 100;
      $total_amt+= $row->items()->sum('sitem_totalamount') * $percent;
    }

    return $total_amt;

  }

  public function exportSales()
  {
    if(!request()->has('conversion') || !request()->has('year')){
      return response()->json([
        'errors' => ['Conversion & year parameters is required.']
      ],422); 
    }
    $conversion = request()->conversion;
    $year = request()->year;
    $month = request()->month !== 'null' ? request()->month : -1;
    $customer = request()->customer !== 'undefined' ? request()->customer : -1;

    return Excel::download(new SmsSalesExport($conversion, $year, $month, $customer), 'sales.xlsx');
  }

  public function exportSalesSummary()
  {

    if(!request()->has('month') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        'errors' => ['Month, year & conversion parameters are required.']
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
        'errors' => ['Month, year & conversion parameters are required.']
      ],422); 
    }
    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;

    return Excel::download(new SmsSalesSummaryExternal($month,$year,$conversion), 'salesSummaryExternal.xlsx');
  }

  public function exportSoa()
  {
    $currency_arr = array('PHP','USD','php','usd');
    if(!request()->has('cid') || !request()->has('currency')  || !in_array(request()->currency,$currency_arr)){
      return response()->json([
        'errors' => ['Customer ID and currency parameters are required.']
      ],422); 
    }

    $customer = SalesCustomer::findOrFail(request()->cid)->c_customername;

    if(strtolower($customer) == 'no customer'){
      return response()->json([
        'errors' => ['No-customer is not allowed.']
      ],422);
    }

    $cid = request()->cid;
    $currency = request()->currency;

    return Excel::download(new SmsSOA($cid,$currency), 'SmsSOA.xlsx');
  }

  public function exportArWeekly()
  {
    if(!request()->has('week') || !request()->has('year') || !request()->has('conversion')){
      return response()->json([
        'errors' => ['Week, year & conversion parameters are required.']
      ],422); 
    }

    $week = request()->week;
    $year = request()->year;
    $conversion = request()->conversion;

    return Excel::download(new SmsSalesWeeklyAR($week,$year,$conversion), 'weeklyAR.xlsx');
  }

  public function test()
  {

    // if(!request()->has('week') || !request()->has('year') || !request()->has('conversion')){
    //   return response()->json([
    //     'errors' => ['Week, year & conversion parameters are required.']
    //   ],422); 
    // }

    // $week = request()->week;
    // $year = request()->year;
    // $conversion = request()->conversion;

    // return Excel::download(new SmsSalesWeeklyAR($week,$year,$conversion), 'weeklyAR.xlsx');
  }

  private $salesRules = array();

  public function __construct(){
    $this->salesRules = array(
      'customer' => 'integer|required',
      'delivery_date' => 'date|required|before_or_equal:'.date('Y-m-d'),
      'currency' => 'string|max:5|in:PHP,USD|required',
      'or_num' => 'sometimes|required_if:markAsPaid,true',
      'date_collected' => 'sometimes|required_if:markAsPaid,true|nullable|date|before_or_equal:'.date('Y-m-d'),
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
      'payment_terms' => $customer->c_paymentterms,
    );
   });

    return response()->json(
     [
      'customersList' => $customers
    ]);

  }

  public function addCustomer(Request $request)
  {

    $validator = Validator::make($request->all(),[
      'customer_name' => 'string|max:150|required|unique:salesms_customers,c_customername',
      'payment_terms' => 'min:1|integer|min:1'
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $customer = new SalesCustomer();  
    $customer->fill([
      'c_customername' => strtoupper($request->customer_name),
      'c_paymentterms' => $request->payment_terms
    ]);
    $customer->save();
    $newCustomer = array(
      'id' => $customer->id,
      'customer_name' => $customer->c_customername,
      'payment_terms' => $customer->c_paymentterms,
    );

    return response()->json(
      [
        'newCustomer' => $newCustomer,
        'message' => 'Record added'
      ]); 

  }

  public function updateCustomer(Request $request,$id)
  {

    $validator = Validator::make($request->all(),[
      'customer_name' => 'string|max:150|required|unique:salesms_customers,c_customername,'.$id,
      'payment_terms' => 'min:1|integer|min:1'
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $customer = SalesCustomer::findOrFail($id);  
    if(strtolower($customer->c_customername) == 'no customer'){
      return response()->json(['errors' => ['No-customer is not allowed.']],422);
    }
    $customer->update([
      'c_customername' => strtoupper($request->customer_name),
      'c_paymentterms' => $request->payment_terms
    ]);

    $newCustomer = array(
      'id' => $customer->id,
      'customer_name' => $customer->c_customername,
      'payment_terms' => $customer->c_paymentterms,
    );

    return response()->json(
      [
        'newCustomer' => $newCustomer,
        'message' => 'Record updated'
      ]); 

  }

  public function deleteCustomer($id)
  {

    $customer = SalesCustomer::findOrFail($id);
    if(strtolower($customer->c_customername) == 'no customer'){
      return response()->json(['errors' => ['No-customer is not allowed.']],422);
    }
    $customer->delete();

    return response()->json([
      'message' => 'Record deleted'
    ]);
  }

  public function selectInvoice()
  {
    return [
      'id',
      's_customer_id',
      's_invoicenum as invoice_num',
      's_deliverydate as delivery_date',
      's_currency as currency',
      's_invoicenum as invoice_num',
      's_withholding as withholdingtax',
      's_ornumber as or_num',
      's_datecollected as date_collected',
      's_isRevised as isRevised',
      'deleted_at as isCancelled'
    ];
  }

  public function invoiceWiths()
  {
    return [
      'customer:c_customername as customer_name,c_paymentterms as payment_terms,id',
      'items' => function($q){
        $q->select([
          'id',
          'sitem_drnum as dr',
          'sitem_ponum as po',
          'sitem_partnum as partnum',
          'sitem_quantity as quantity',
          'sitem_unitprice as unitprice',
          'sitem_remarks as remarks',
          'sitem_totalamount as totalAmount',
          'sitem_partnum as partnum',
          'sitem_sales_id',
        ]);
      }];
    }

    public function getInvoicesForCustomer($customerId)
    {

      $customer = SalesCustomer::findOrFail($customerId)->c_customername;

      if(strtolower($customer) == 'no customer'){
        return response()->json([
          'invoicesList' => []
        ]);
      }

      $invoices = SalesInvoice::whereHas('customer', function($q) use ($customerId){
        return $q->where('s_customer_id',$customerId);
      })
      ->where('s_datecollected',NULL)
      ->where('s_ornumber',NULL)
      ->where('s_isRevised',0)
      ->orderBy('s_invoicenum','DESC')
      ->pluck('s_invoicenum')
      ->toArray('s_invoicenum');

      return response()->json([
        'invoicesList' => $invoices
      ]);
    }

    public function getSales()
    {
      $pageSize = request()->pageSize;

      $q = SalesInvoice::query();
      $q->select($this->selectInvoice());
      $q->with($this->invoiceWiths());

      if(request()->has('showRecord')){
        $showRecord = request()->showRecord;
        if($showRecord == 'All'){
          $q->withTrashed();
        }else if($showRecord == 'Collected'){
          $q->where('s_ornumber','!=',NULL)
          ->where('s_datecollected','!=',NULL);
        }else if($showRecord == 'NotCollected' || $showRecord == 'Due'){
          $q->where('s_ornumber',NULL)
          ->where('s_datecollected',NULL)
          ->where('s_isRevised',0);
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
        $q->whereYear('s_deliverydate',request()->year);
      }

      if(request()->has('orNumber')){
        $q->where('s_ornumber','LIKE','%'.request()->orNumber.'%');
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

      if(request()->has('showRecord') && request()->showRecord == 'Due'){
          $salesRes = $q->get();
          $salesTotal = count($salesRes);          
          $salesList = $salesRes->filter(function ($value, $key) {
            return Carbon::parse($value->delivery_date)->diffInDays(Carbon::now()) > $value->customer->payment_terms
              && $value->customer->customer_name != 'NO CUSTOMER';
          })->forPage(request()->page,$pageSize)
            ->values();
        }else{
          $salesRes = $q->paginate($pageSize);
          $salesTotal = $salesRes->total();
          $salesList = $salesRes->items();
        }
      
      return response()->json([
        'salesListLength' => $salesTotal,
        'salesList' => $salesList,
      ]);

    }

    public function createSales(Request $request){

      $validator = Validator::make($request->all(),
        array_merge(['invoice_num' => 
          'string|max:50|required|unique:salesms_invoice,s_invoicenum']
          ,$this->salesRules));

      $validator->sometimes('invoiceItems', 'array|min:1|required', function($input){
        return $input->cancelled == false;
      });

      if($validator->fails()){
        return response()->json(['errors' => $validator->errors()->all()],422);
      }

      $sales = new SalesInvoice();
      $sales->fill($this->invoiceInputArray($request));
      $sales->save();

      if($request->cancelled)
        $sales->delete();

      foreach($request->invoiceItems as $item){
        $sales->items()->create($this->invoiceInputItemArray($item));
      }

      $sales->refresh();
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

      $validator->sometimes('invoiceItems', 'array|min:1|required', function($input){
        return $input->cancelled == false;
      });

      if($validator->fails()){
        return response()->json(['errors' => $validator->errors()->all()],422);
      }

      $sales = SalesInvoice::findOrFail($id);
      $sales->fill($this->invoiceInputArray($request));

      if($sales->isDirty()){
        $sales->save();
      }

      if($request->cancelled)
        $sales->delete();

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
      'date_collected' => 'required_if:markAsPaid,true|nullable|before_or_equal:'.date('Y-m-d'),
      'withholdingtax' => 'integer|nullable|min:0',
    ],[],
    [
      'invoiceKeys' => 'Invoice id'
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    SalesInvoice::whereIn('s_invoicenum',$request->invoiceKeys)->update([
      's_ornumber' => $request->or_num,
      's_datecollected' => $request->date_collected,
      's_withholding' => $request->withholdingtax,
    ]);

    $sales = SalesInvoice::whereIn('s_invoicenum',$request->invoiceKeys)
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

    return array(
      'id' => $invoice->id,
      'invoice_num' => $invoice->s_invoicenum,
      'delivery_date' => $invoice->s_deliverydate,
      'currency' => $invoice->s_currency,
      'customer' => array(
        'id' => $invoice->customer->id,
        'customer_name' => $invoice->customer->c_customername,
        'payment_terms' => $invoice->customer->c_paymentterms,
      ),
      'withholdingtax' => $invoice->s_withholding,
      'or_num' => $invoice->s_ornumber,
      'date_collected' => $invoice->s_datecollected,
      'isRevised' => $invoice->s_isRevised,
      'isCancelled' => $invoice->deleted_at,
      'items' => $invoice->items->map(function($item) {
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
      'unitprice' => number_format($item->sitem_unitprice,4,'.',''),
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
      'sitem_unitprice' => number_format($item['unitprice'],4,'.',''),
      'sitem_totalamount' => doubleval($item['unitprice']) 
        * intval($item['quantity']),
    );
  }

  public function searchOR(){

    $search = request()->has('search') ? request()->search : '';

    $or = SalesInvoice::where('s_ornumber', $search)
      ->get()
      ->map(function($sales) {
        return array(
          'id' => $sales->id,
          'customer' => $sales->customer->c_customername,
          'invoice' => $sales->s_invoicenum,
          'deliveryDate' => $sales->s_deliverydate,
          'currency' => $sales->s_currency,
          'invoiceTotal' => $sales->items->sum('sitem_totalamount'),
          'orNumber' => $sales->s_ornumber,
        );
      })
      ->values();

    return response()->json([
      'orList' => $or
    ]);
  }
}
