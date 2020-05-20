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

class PurchasesController extends Controller
{
  // purchases items
  //utils functions
  public function getItem($item) {
    return array(
      'id' => $item->id,
      'dateReceived' => $item->item_datereceived,
      'datePurchased' => $item->item_datepurchased,
      'supplier' => $item->supplier->id ?? '',
      'supplierLabel' => $item->supplier->supplier_name ?? '',
      'accounts' => $item->account->id ?? '',
      'accountsLabel' => $item->account->accounts_name ?? '',
      'invoice' => $item->item_salesinvoice_no,
      'deliveryReceipt' => $item->item_deliveryreceipt_no,
      'purchaseOrderNo' => $item->item_purchaseorder_no,
      'purchaseRequestNo' => $item->item_purchaserequest_no,
      'particular' => $item->item_particular,
      'quantity' => $item->item_quantity,
      'unit' => $item->item_unit,
      'unitPrice' => $item->item_unitprice,
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
      'item_unitprice' => $input->unitPrice,
    );
  }

  

  public function getAccounts() {
    $accounts = AccountingPurchasesAccounts::select(
        'id',
        'accounts_code as code',
        'accounts_name as account',
        'accounts_requiredInvoice as requiredInvoice'
      )->get();

    return response()->json([
      'accountsList' => $accounts
    ]);

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
      'supplier_name as supplierLabel',
      'purchasesms_accounts.id as accounts',
      'accounts_name as accountsLabel',
      'item_salesinvoice_no as invoice',
      'item_deliveryreceipt_no as deliveryReceipt',
      'item_purchaseorder_no as purchaseOrderNo',
      'item_purchaserequest_no as purchaseRequestNo',
      'item_particular as particular',
      'item_quantity as quantity',
      'item_unit as unit',
      'item_unitprice as unitPrice'
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
        'invoice' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'deliveryReceipt' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'purchaseOrderNo' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'purchaseRequestNo' => 'string|min:3|max:50|nullable',
        'particular' => 'string|required|min:1|max:150',
        'quantity' => 'integer|required|min:1',
        'unit' => 'string|required|min:1|max:50',
        'unitPrice' => 'integer|required|min:0',
      ),
      [],
      array('isInvoiceRequired' => 'invoice required')
    );

    $validator->sometimes('particular',
      'unique:purchasesms_items,item_particular,null,id,item_salesinvoice_no,'.$request->invoice,
      function($input) {
        return strtoupper($input->invoice) != "NA" || strtoupper($input->invoice) != "N/A"
          || $input->invoice != "";
    });

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $item = new AccountingPurchasesItems();
    $item->fill($this->itemInputArray($request));
    $item->save();

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
        'invoice' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'deliveryReceipt' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'purchaseOrderNo' => 'string|required_if:isInvoiceRequired,true|min:3|max:50',
        'purchaseRequestNo' => 'string|min:3|max:50|nullable',
        'particular' => 'string|required|min:1|max:150',
        'quantity' => 'integer|required|min:1',
        'unit' => 'string|required|min:1|max:50',
        'unitPrice' => 'integer|required|min:0',
      ),
      [],
      array('isInvoiceRequired' => 'invoice required')
    );

    $validator->sometimes('particular',
      'unique:purchasesms_items,item_particular,'.$id.',id,item_salesinvoice_no,'.$request->invoice,
      function($input) {
        return strtoupper($input->invoice) != "NA" || strtoupper($input->invoice) != "N/A"
          || $input->invoice != "";
    });

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    $item->fill($this->itemInputArray($request));

    if($item->isDirty()){
      $item->save();
    }

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
        'supplier_name as supplier',
        'supplier_payment_terms as terms',
        'supplier_address as address',
        'supplier_tin_number as tin'  
      )->get();

    return response()->json([
      'suppliersList' => $suppliers
    ]);
  }

  public function addSupplier(Request $request) {
    $validator = Validator::make(
      $request->all(),
      array(
        'supplier' => 'string|min:1|max:150|required
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
      'supplier' => $supplier->supplier_name,
      'terms' => $supplier->supplier_payment_terms,
      'address' => $supplier->supplier_address,
      'tin' => $supplier->supplier_tin_number,
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
        'supplier' => 'string|min:1|max:150|required
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
      'supplier' => $supplier->supplier_name,
      'terms' => $supplier->supplier_payment_terms,
      'address' => $supplier->supplier_address,
      'tin' => $supplier->supplier_tin_number,
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

}
