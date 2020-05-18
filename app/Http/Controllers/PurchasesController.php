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
      'supplier' => $item->supplier->id,
      'supplierLabel' => $item->supplier->supplier_name,
      'accounts' => $item->account->id,
      'accountsLabel' => $item->account->accounts_name,
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


  //cruds
  public function getItems() {
    $q = AccountingPurchasesItems::query();
    $q->leftJoin('purchasesms_accounts', function($join){
      $join->on('purchasesms_accounts.id','=','purchasesms_items.item_accounts_id');
    })->leftJoin('purchasesms_supplier', function($join){
      $join->on('purchasesms_supplier.id','=','purchasesms_items.item_supplier_id');
    });

    $q->select(
      'purchasesms_items.id',
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
        'purchaseRequestNo' => 'string|min:3|max:50',
        'particular' => 'string|required|min:1|max:150',
        'quantity' => 'integer|required|min:1',
        'unit' => 'string|required|min:1|max:50',
        'unitPrice' => 'integer|required|min:0',
      ),
      [],
      array('isInvoiceRequired' => 'invoice required')
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()], 422);
    }

    // 'item_datereceived' => $input->dateReceived,
    // 'item_datepurchased' => $input->datePurchased,
    // 'item_supplier_id' => $input->supplier,
    // 'item_accounts_id' => $input->accounts,
    // 'item_salesinvoice_no' => $input->invoice,
    // 'item_deliveryreceipt_no' => $input->deliveryReceipt,
    // 'item_purchaseorder_no' => $input->purchaseOrderNo,
    // 'item_purchaserequest_no' => $input->purchaseRequestNo,
    // 'item_particular' => $input->particular,
    // 'item_quantity' => $input->quantity,
    // 'item_unit' => $input->unit,
    // 'item_unitprice' => $input->unitPrice,

    $item = new AccountingPurchasesItems();
    $item->fill($this->itemInputArray($request));
    $item->save();

    $newItem = $this->getItem($item);
    return response()->json([
      'newItem' => $newItem,
      'message' => 'Record successfully added',
    ]);
  }

}
