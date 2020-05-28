<?php

namespace App\Http\Controllers;

use App\Http\Controllers\LogsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use DB;
use Excel;
use PDF;
use Carbon\Carbon;

use App\AccountingPurchasesAccounts;
use App\AccountingPurchasesItems;
use App\AccountingPurchasesSupplier;

use App\Exports\AccountingPurchasesBirMonthly;
use App\Exports\AccountingPayablesReport;
use App\Exports\AccountingPurchasesReport;
use App\Exports\AccountingPayablesSummary;

class PurchasesController extends Controller
{ 

  private $monthsArray = array(
    'jan' => 1,
    'feb' => 2,
    'mar' => 3,
    'apr' => 4,
    'may' => 5,
    'jun' => 6,
    'jul' => 7,
    'aug' => 8,
    'sep' => 9,
    'oct' => 10,
    'nov' => 11,
    'dec' => 12,
  );

  //export
  public function exportBirMonthly() {
    if(!request()->has('month')
      || !request()->has('year')
      || !request()->has('conversion')
      || !request()->has('company')
    )
    return response()->json(['errors' => ['Invalid parameters']],422);

    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;
    $company = request()->company;

    return Excel::download(new AccountingPurchasesBirMonthly($conversion, $year, $month, $company), $company.'-bir-report'.$month.'-'.$year.'.xlsx');
  }

  public function exportPayablesReport() {
    if(!request()->has('month')
      || !request()->has('year')
      || !request()->has('company')
    )
    return response()->json(['errors' => ['Invalid parameters']],422);

    $company = request()->company;
    $month = request()->month;
    $year = request()->year;
    return Excel::download(new AccountingPayablesReport(), $company.'-payables'.$month.'-'.$year.'.xlsx');
  }

  public function exportPurchasesReport() {
    if(!request()->has('month')
      || !request()->has('year')
      || !request()->has('conversion')
      || !request()->has('company')
    )
    return response()->json(['errors' => ['Invalid parameters']],422);

    $month = request()->month;
    $year = request()->year;
    $conversion = request()->conversion;
    $company = request()->company;

    return Excel::download(new AccountingPurchasesReport(), $company.'-purchases-'.$month.'-'.$year.'.xlsx');
  }

  public function exportAccountsPayablesSummary() {
    if(!request()->has('month')
      || !request()->has('year')
      || !request()->has('company')
    )
    return response()->json(['errors' => ['Invalid parameters']],422);
    $month = request()->month;
    $year = request()->year;
    $company = request()->company ?? 'no_companyname';
  
    return Excel::download(new AccountingPayablesSummary(), $company.'-apsummary-'.$month.'-'.$year.'.xlsx');
  }

  // purchases items
  //utils functions
  public function getItem($item) {
    return array(
      'id' => $item->id,
      'status' => $item->ap ? "PAID" : "NOT PAID",
      'dateReceived' => $item->item_datereceived,
      'datePurchased' => $item->item_datepurchased,
      'supplier' => $item->supplier->id ?? '',
      'supplierLabel' => strtoupper($item->supplier->supplier_name) ?? '',
      'accounts' => $item->account->id ?? '',
      'accountsLabel' => strtoupper($item->account->accounts_code) ?? strtoupper($item->account->accounts_name),
      'invoice' => strtoupper($item->item_salesinvoice_no),
      'deliveryReceipt' => strtoupper($item->item_deliveryreceipt_no),
      'purchaseOrderNo' => strtoupper($item->item_purchaseorder_no),
      'purchaseRequestNo' => strtoupper($item->item_purchaserequest_no),
      'particular' => strtoupper($item->item_particular),
      'quantity' => $item->item_quantity,
      'unit' => $item->item_unit,
      'currency' => $item->item_currency,
      'unitPrice' => $item->item_unitprice,
      'withHoldingTax' => $item->ap->ap_withholding ?? null,
      'officialReceipt' => $item->ap->ap_officialreceipt_no ?? '',
      'paidByCheck' => $item->ap->ap_is_check ?? '',
      'checkNo' => $item->ap->ap_check_no ?? '',
      'bankName' => $item->ap->ap_bankname ?? '',
      'paymentDate' => $item->ap->ap_payment_date ?? '',
      'isInvoiceRequired' => $item->account->accounts_requiredInvoice,
      'withUnreleasedCheck' => $item->item_with_unreleasedcheck ? 'YES' : 'NO'
    );
  }

  public function itemInputArray($input) {
    return array(
      'item_datereceived' => $input->dateReceived,
      'item_datepurchased' => $input->datePurchased,
      'item_supplier_id' => $input->supplier,
      'item_accounts_id' => $input->accounts,
      'item_salesinvoice_no' => $input->invoice,
      'item_deliveryreceipt_no' => $input->deliveryReceipt,
      'item_purchaseorder_no' => $input->purchaseOrderNo,
      'item_purchaserequest_no' => $input->purchaseRequestNo,
      'item_particular' => $input->particular,
      'item_quantity' => $input->quantity,
      'item_unit' => $input->unit,
      'item_currency' => $input->currency,
      'item_unitprice' => $input->unitPrice,
      'item_with_unreleasedcheck' => $input->markAsPaid ? false : $input->withUnreleasedCheck,
    );
  }

  //cruds
  public function getItems() {
    $q = AccountingPurchasesItems::query();
    $q->leftJoin('purchasesms_accounts', function($join){
      $join->on('purchasesms_accounts.id','=','purchasesms_items.item_accounts_id');
    })->leftJoin('purchasesms_supplier', function($join){
      $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
    })->leftJoin('purchasesms_apdetails', function($join){
      $join->on('purchasesms_items.id','=','purchasesms_apdetails.ap_item_id');
    });

    $q->select(
      'purchasesms_items.id',
      DB::raw('IF(ap_item_id > 0,"PAID","NOT PAID") as status'),
      'item_datereceived as dateReceived',
      'item_datepurchased as datePurchased',
      'purchasesms_supplier.id as supplier',
      Db::raw('UPPER(supplier_name) as supplierLabel'),
      'purchasesms_accounts.id as accounts',
      Db::raw('UPPER(IF(accounts_code != "",accounts_code,accounts_name)) as accountsLabel'),
      Db::raw('UPPER(item_salesinvoice_no) as invoice'),
      Db::raw('UPPER(item_deliveryreceipt_no) as deliveryReceipt'),
      Db::raw('UPPER(item_purchaseorder_no) as purchaseOrderNo'),
      Db::raw('UPPER(item_purchaserequest_no) as purchaseRequestNo'),
      Db::raw('UPPER(item_particular) as particular'),
      'item_quantity as quantity',
      'item_unit as unit',
      'item_currency as currency',
      'item_unitprice as unitPrice',
      'ap_withholding as withHoldingTax',
      Db::raw('IF(ap_is_check,true,false) as paidByCheck'),
      Db::raw('IFNULL(ap_officialreceipt_no,"") as officialReceipt'),
      Db::raw('IFNULL(ap_check_no,"") as checkNo'),
      Db::raw('IFNULL(ap_bankname,"") as bankName'),
      Db::raw('IFNULL(ap_payment_date,"") as paymentDate'),
      'accounts_requiredInvoice as isInvoiceRequired',
      Db::raw('IF(item_with_unreleasedcheck,"YES","NO") as withUnreleasedCheck')
    );

    //sorts & filter
    if(request()->has('search')){
      $search = '%'.request()->search.'%';
      $q->where('item_salesinvoice_no', 'LIKE', $search)
        ->orWhere('item_particular', 'LIKE', $search)
        ->orWhere('item_purchaseorder_no', 'LIKE', $search)
        ->orWhere('item_purchaserequest_no', 'LIKE', $search);
    }

    if(request()->has('supp')) {
      $q->whereRaw('purchasesms_supplier.id = ?',array(request()->supp));
    }

    if(request()->has('acc')) {
      $q->whereRaw('purchasesms_accounts.id = ?',array(request()->acc));
    }

    if(request()->has('status')) {
      $status = strtolower(request()->status);
      if($status == "paid")
        $q->has('ap');
      else if($status == "not")
        $q->doesntHave('ap');
    }
    
    $sort = strtolower(request()->sort);
    if($sort == "received_desc")
      $q->latest('item_datereceived');
    else if($sort == "received_asc")
      $q->oldest('item_datereceived');
    else if($sort == "purchased_desc")
      $q->latest('item_datepurchased');
    else if($sort == "purchased_asc")
      $q->oldest('item_datepurchased');
    else if($sort == "asc")
      $q->oldest('purchasesms_items.id');
    else
      $q->latest('purchasesms_items.id');

    $list = $q->paginate(1000);

    return response()->json([
      'itemsListLength' => $list->total(),
      'itemsList' => $list->items(),
    ]);
  }

  public function addItem(Request $request) {
    $validator = Validator::make($request->all(),
      array(
        'dateReceived' => 'date|required|before_or_equal:'.date('Y-m-d'),
        'datePurchased' => 'date|required|before_or_equal:'.date('Y-m-d'),
        'supplier' => 'integer|required|min:1',
        'accounts' => 'integer|required|min:1',
        'invoice' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'deliveryReceipt' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'purchaseOrderNo' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'purchaseRequestNo' => 'string|min:3|max:50|nullable|regex:/^[a-zA-Z0-9 _-]*$/',
        'particular' => 'string|required|min:1|max:150|regex:/^[a-zA-Z0-9 '."'".'"_-]*$/',
        'quantity' => 'numeric|required|min:1',
        'unit' => 'string|required|min:1|max:50',
        'unitPrice' => 'numeric|required|min:0',
        'withHoldingTax' => 'numeric|min:0|max:100|nullable',//ap validation
        'officialReceipt' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:markAsPaid,true
          |nullable',
        'paidByCheck' => 'boolean',
        'markAsPaid' => 'boolean',
        'bankName' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true
          |required_if:markAsPaid,true|nullable',
        'checkNo' => 'string|max:50|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true
          |required_if:markAsPaid,true|nullable',
        'paymentDate' => 'date|required_if:paidByCheck,true|before_or_equal:'.date('Y-m-d'),
        'currency' => 'string|min:1|max:3|in:PHP,USD,php,usd|required',
        'withUnreleasedCheck' => 'boolean'
      ),
      [],
      array('isInvoiceRequired' => 'invoice required')
    );

    $validator->sometimes('particular',
      'unique:purchasesms_items,item_particular,null,id,item_salesinvoice_no,'.$request->invoice,
      function($input) {
        return strtoupper($input->invoice) != "NA" && strtoupper($input->invoice) != "N/A"
          && $input->invoice != "";
    });

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $item = new AccountingPurchasesItems();
    $item->fill($this->itemInputArray($request));
    $item->save();

    if($request->markAsPaid) {
      $item->ap()->create([
        'ap_withholding' => $request->withHoldingTax,
        'ap_officialreceipt_no' => $request->officialReceipt,
        'ap_is_check' => $request->paidByCheck,
        'ap_check_no' => $request->checkNo,
        'ap_bankname' => $request->bankName,
        'ap_payment_date' => $request->paymentDate,
      ]);
    }

    $newItem = $this->getItem($item);
    return response()->json([
      'newItem' => $newItem,
      'message' => 'Record successfully added',
    ]);
  }

  public function updatedItem(Request $request, $id) {
    $item = AccountingPurchasesItems::findOrFail($id);

    $validator = Validator::make($request->all(),
      array(
        'dateReceived' => 'date|required|before_or_equal:'.date('Y-m-d'),
        'datePurchased' => 'date|required|before_or_equal:'.date('Y-m-d'),
        'supplier' => 'integer|required|min:1',
        'accounts' => 'integer|required|min:1',
        'invoice' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'deliveryReceipt' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'purchaseOrderNo' => 'string|required_if:isInvoiceRequired,true|min:3|max:50|regex:/^[a-zA-Z0-9 _-]*$/',
        'purchaseRequestNo' => 'string|min:3|max:50|nullable|regex:/^[a-zA-Z0-9 _-]*$/',
        'particular' => 'string|required|min:1|max:150|regex:/^[a-zA-Z0-9 '."'".'"_-]*$/',
        'quantity' => 'numeric|required|min:1',
        'unit' => 'string|required|min:1|max:50',
        'unitPrice' => 'numeric|required|min:0',
        'withHoldingTax' => 'numeric|min:0|max:100|nullable',//ap validation
        'officialReceipt' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:markAsPaid,true
          |nullable',
        'paidByCheck' => 'boolean',
        'bankName' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true
          |required_if:markAsPaid,true|nullable',
        'checkNo' => 'string|max:50|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true
          |required_if:markAsPaid,true|nullable',
        'paymentDate' => 'date|required_if:paidByCheck,true|nullable
          |before_or_equal:'.date('Y-m-d'),
        'currency' => 'string|min:1|max:3|in:PHP,USD,php,usd|required',
        'withUnreleasedCheck' => 'boolean'
      ),
      [],
      array('isInvoiceRequired' => 'invoice required')
    );

    $validator->sometimes('particular',
      'unique:purchasesms_items,item_particular,'.$id.',id,item_salesinvoice_no,'.$request->invoice,
      function($input) {
        return strtoupper($input->invoice) != "NA" && strtoupper($input->invoice) != "N/A"
          && $input->invoice != "";
    });

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $item->fill($this->itemInputArray($request));

    if($item->isDirty()){
      $item->save();
    }

    if($request->markAsPaid) {
      if($item->ap) {
        $item->ap()->update([
          'ap_withholding' => $request->withHoldingTax,
          'ap_officialreceipt_no' => $request->officialReceipt,
          'ap_is_check' => $request->paidByCheck,
          'ap_check_no' => $request->checkNo,
          'ap_bankname' => $request->bankName,
          'ap_payment_date' => $request->paymentDate,
        ]);
      } else {
        $item->ap()->create([
          'ap_withholding' => $request->withHoldingTax,
          'ap_officialreceipt_no' => $request->officialReceipt,
          'ap_is_check' => $request->paidByCheck,
          'ap_check_no' => $request->checkNo,
          'ap_bankname' => $request->bankName,
          'ap_payment_date' => $request->paymentDate,
        ]);
      }
    }else
      $item->ap()->delete();

    $item->refresh();
    $newItem = $this->getItem($item);
    return response()->json([
      'newItem' => $newItem,
      'message' => 'Record successfully udpated',
    ]);
  }

  public function deleteItem($id){
    $item = AccountingPurchasesItems::findOrFail($id);
    $item->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  ///suppliers
  public function getSuppliers() {

    $suppliers = AccountingPurchasesSupplier::select(
        'id',
        Db::raw('UPPER(supplier_name) as supplier'),
        'supplier_payment_terms as terms',
        Db::raw('UPPER(supplier_address) as address'),
        Db::raw('UPPER(supplier_tin_number) as tin')  
      )->get();

    return response()->json([
      'suppliersList' => $suppliers
    ]);
  }

  public function addSupplier(Request $request) {
    $validator = Validator::make(
      $request->all(),
      array(
        'supplier' => 'string|regex:/^[a-zA-Z0-9 _-]*$/|min:1|max:150|required
          |unique:purchasesms_supplier,supplier_name',
        'terms' => 'integer|min:0|nullable',
        'address' => 'string|min:1|max:300|required',
        'tin' => 'string|min:1|max:50|required',
      )
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $supplier = new AccountingPurchasesSupplier();
    $supplier->fill(array(
      'supplier_name' => $request->supplier,
      'supplier_payment_terms' => $request->terms,
      'supplier_address' => $request->address,
      'supplier_tin_number' => $request->tin,
    ));
    $supplier->save();

    $newSupplier = array(
      'id' => $supplier->id,
      'supplier' => strtoupper($supplier->supplier_name),
      'terms' => $supplier->supplier_payment_terms,
      'address' => strtoupper($supplier->supplier_address),
      'tin' => strtoupper($supplier->supplier_tin_number),
    );

    return response()->json([
      'newSupplier' => $newSupplier,
      'message' => 'Record added',
    ]);
  }


  public function updateSupplier(Request $request, $id) {
    $validator = Validator::make(
      $request->all(),
      array(
        'supplier' => 'string|regex:/^[a-zA-Z0-9 _-]*$/|min:1|max:150|required
          |unique:purchasesms_supplier,supplier_name,'.$id,
        'terms' => 'integer|min:0|nullable',
        'address' => 'string|min:1|max:300|required',
        'tin' => 'string|min:1|max:50|required',
      )
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $supplier = AccountingPurchasesSupplier::findOrFail($id);
    $supplier->fill(array(
      'supplier_name' => $request->supplier,
      'supplier_payment_terms' => $request->terms,
      'supplier_address' => $request->address,
      'supplier_tin_number' => $request->tin,
    ));
    
    if($supplier->isDirty()){
      $supplier->save();
    }

    $newSupplier = array(
      'id' => $supplier->id,
      'supplier' => strtoupper($supplier->supplier_name),
      'terms' => $supplier->supplier_payment_terms,
      'address' => strtoupper($supplier->supplier_address),
      'tin' => strtoupper($supplier->supplier_tin_number),
    );

    return response()->json([
      'newSupplier' => $newSupplier,
      'message' => 'Record updated',
    ]);
  }

  public function deleteSupplier($id){
    $supplier = AccountingPurchasesSupplier::findOrFail($id);
    $supplier->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  public function getAccounts() {
    $accounts = AccountingPurchasesAccounts::select(
        'id',
        DB::raw('upper(accounts_code) as code'),
        DB::raw('upper(accounts_name) as account'),
        'accounts_requiredInvoice as requiredInvoice'
      )->get();

    return response()->json([
      'accountsList' => $accounts
    ]);
  }

  public function addAccount(Request $request) {
    $validator = Validator::make(
      $request->all(),
      array(
        'account' => 'string|regex:/^[a-zA-Z0-9 _-]*$/|min:1|max:250|required
          |unique:purchasesms_accounts,accounts_name',
        'code' => 'string|regex:/^[a-zA-Z0-9_-]*$/|min:1|max:500|nullable',
        'requiredInvoice' => 'boolean|required',
      )
    );

    $validator->sometimes('code', 'unique:purchasesms_accounts,accounts_code',
      function($input) {
        return $input->code != null && $input->code != "";
      }
    );

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()] ,422);

    $account = new AccountingPurchasesAccounts();
    $account->fill(array(
      'accounts_code' => $request->code,
      'accounts_name' => $request->account,
      'accounts_requiredInvoice' => $request->requiredInvoice
    ));
    $account->save();

    $newAccount = array(
      'id' => $account->id,
      'code' => strtoupper($account->accounts_code),
      'account' => strtoupper($account->accounts_name),
      'requiredInvoice' => $account->accounts_requiredInvoice,
    );

    return response()->json([
      'newAccount' => $newAccount,
      'message' => 'Record added',
    ]);

  }

  public function updateAccount(Request $request, $id) {
    $validator = Validator::make(
      $request->all(),
      array(
        'account' => 'string|regex:/^[a-zA-Z0-9 _-]*$/|min:1|max:250|required
          |unique:purchasesms_accounts,accounts_name,'.$id,
        'code' => 'string|regex:/^[a-zA-Z0-9_-]*$/|min:1|max:500|nullable',
        'requiredInvoice' => 'boolean|required',
      )
    );

    $validator->sometimes('code', 'unique:purchasesms_accounts,accounts_code,'.$id,
      function($input) {
        return $input->code != null && $input->code != "";
      }
    );

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()] ,422);

    $account = AccountingPurchasesAccounts::findOrFail($id);
    $account->fill(array(
      'accounts_code' => $request->code,
      'accounts_name' => $request->account,
      'accounts_requiredInvoice' => $request->requiredInvoice
    ));

    if($account->isDirty()){
      $account->save();
    }

    $newAccount = array(
      'id' => $account->id,
      'code' => strtoupper($account->accounts_code),
      'account' => strtoupper($account->accounts_name),
      'requiredInvoice' => $account->accounts_requiredInvoice,
    );

    return response()->json([
      'newAccount' => $newAccount,
      'message' => 'Record updated',
    ]);

  }

  public function deleteAccount($id){
    $account = AccountingPurchasesAccounts::findOrFail($id);
    $account->delete();

    return response()->json([
      'message' => 'Record deleted',
    ]);
  }


  ///ap

  public function getItemsBySupplier($supplierId) {
    $q = AccountingPurchasesItems::query();
    $q->leftJoin('purchasesms_accounts', function($join){
      $join->on('purchasesms_accounts.id','=','purchasesms_items.item_accounts_id');
    });

    $q->select(
      'purchasesms_items.id',
      'item_datereceived as dateReceived',
      'item_datepurchased as datePurchased',
      'purchasesms_accounts.id as accounts',
      Db::raw('UPPER(IF(accounts_code != "",accounts_code,accounts_name)) as accountsLabel'),
      Db::raw('UPPER(item_salesinvoice_no) as invoice'),
      Db::raw('UPPER(item_deliveryreceipt_no) as deliveryReceipt'),
      Db::raw('UPPER(item_purchaseorder_no) as purchaseOrderNo'),
      Db::raw('UPPER(item_purchaserequest_no) as purchaseRequestNo'),
      Db::raw('UPPER(item_particular) as particular'),
      'item_quantity as quantity',
      'item_unit as unit',
      'item_unitprice as unitPrice'
    );
    $q->whereHas('supplier', function($q) use($supplierId){
      $q->where('id', $supplierId);
    });
    $q->doesntHave('ap');
    $items = $q->latest('purchasesms_items.id')->get();
    return response()->json([
      'items' => $items,
    ]);
  }

  public function addPayment(Request $request) {
    $validator = Validator::make(
      $request->all(),
      array(
        'supplier' => 'required',
        'withHoldingTax' => 'numeric|min:0|max:100|nullable',
        'officialReceipt' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|nullable',
        'paidByCheck' => 'boolean',
        'bankName' => 'string|max:100|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true|nullable',
        'checkNo' => 'string|max:50|regex:/^[a-zA-Z0-9-_ ]*$/|required_if:paidByCheck,true|nullable',
        'paymentDate' => 'date|required|before_or_equal:'.date('Y-m-d'),
        'selectedItems' => 'array|min:1|required',
      )
    );

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()],422);
    $updatedItems = array();

    foreach($request->selectedItems as $id) {
      $item = AccountingPurchasesItems::findOrFaiL($id);
      if($item->item_supplier_id == $id || $item->ap)
        continue;

      $item->ap()->create([
        'ap_withholding' => $request->withHoldingTax,
        'ap_officialreceipt_no' => $request->officialReceipt,
        'ap_is_check' => $request->paidByCheck,
        'ap_check_no' => $request->checkNo,
        'ap_bankname' => $request->bankName,
        'ap_payment_date' => $request->paymentDate,
      ]);

      $item->refresh();
      array_push($updatedItems, $this->getItem($item));
    }

    return response()->json([
      'message' => 'Payment record added',
      'updatedItems' => $updatedItems,
    ]);

  }

}
