<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Units;
use App\CposmsLogs;
use App\PjomsLogs;
use App\PmmsLogs;
use App\WimsLogs;
use App\PrmsLogs;
use App\PsmsLogs;
use App\Customers;
use App\Supplier;
use DB;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{
	public function getUnits()
	{
		// ini_set('max_execution_time', 100000);
		// $lists = DB::table('test')->get();
		// foreach($lists as $item){
		// 	$pi = Db::table('purchasesms_items')->where('id', $item->id)->update([
		// 		'item_unitprice' => $item->unitprice,
		// 	]);

		// 	echo $item->id."<br/>";
		// }
		// foreach($lists as $item){
		// 	DB::table('purchasesms_apdetails')->insert([
		// 		'ap_item_id' => $item->id,
		// 		'ap_withholding' => intval($item->wht),
		// 		'ap_officialreceipt_no' => $item->or_num,
		// 		'ap_is_check' => $item->checknum !== '' ? 1 : 0,
		// 		'ap_check_no' => $item->checknum,
		// 		'ap_payment_date' => $item->datepaid,
		// 	]);
			// $supplier = Db::table('purchasesms_supplier')
			// 	->where('supplier_name', $item->supplier_name)->first();
			// $account = Db::table('purchasesms_accounts')
			// 	->where('accounts_code', $item->code)->first();

			// DB::table('purchasesms_items')->insert([
			// 	'item_datereceived' => $item->date_received,
			// 	'item_datepurchased' => $item->date_purchase,
			// 	'item_supplier_id' => $supplier->id,
			// 	'item_accounts_id' => $account->id,
			// 	'item_salesinvoice_no' => $item->si,
			// 	'item_deliveryreceipt_no' => $item->dr,
			// 	'item_purchaseorder_no' => $item->po,
			// 	'item_purchaserequest_no' => $item->pr,
			// 	'item_particular' => $item->particular,
			// 	'item_quantity' => $item->qty,
			// 	'item_unit' => $item->unit,
			// 	'item_with_unreleasedcheck' => $item->unreleased !== "" ? 1 : 0,
			// 	'item_currency' => $item->currency,
			// ]);
		// 	echo intval($item->wht)."<br/>";
		// }
		// $customer = DB::table('test')->groupBy('code')->get();
		// foreach($customer as $custom){
		// 	DB::table('purchasesms_accounts')->insert([
		// 		'accounts_code' => $custom->code,
		// 		'accounts_name' => $custom->code,
		// 	]);
		// }
		$units = Units::all()->pluck('unit')->toArray();
		return response()->json(
			[
				'unitsOption' => $units
			]);
	}

	public function getBeforeAndAfter($details = array()){
		//index 0 = names, 1 = dirty, 2 = original, 3 = condition, 4 = actions if condition is true;

		$before = "";
		$after = "";

		$names = $details[0];
		$dirty = $details[1];
		$original = $details[2];

		$condition = array_key_exists(3,$details);

		foreach($dirty as $key => $dirt){

			$orig_val = $original[$key];
			$dirt_val = $dirt;

			if(isset($details[3]) && $key == $details[3]){
				$model = $details[4]['model'];
				$cols = $details[4]['cols'];

				$orig_val = $model->find($orig_val)->$cols;
				$dirt_val = $model->find($dirt)->$cols;
			}

			$before .= $names[$key]." : ".$orig_val.",";
			$after .= $names[$key]." : ".$dirt_val.",";

		}

		return ['before' => substr($before, 0,-1),'after' => substr($after, 0,-1)];
	}

	public function getLogs($q)
	{
		$logs_arr = array();

		$pageSize = request()->pageSize;
		if(request()->has('search')){
			$search = "%".request()->search."%";
			$q->where('action','like',$search)
			->orWhere('before','like',$search)
			->orWhere('after','like',$search);
		}

		$logs = $q->latest()->paginate($pageSize);
		foreach($logs as $log)
		{
			$format = $log->created_at->format('Y-m-d h:i:s');
			$diff = $log->created_at->diffForHumans();
			array_push($logs_arr,
				array(
					'id' => $log->id,
					'user' => $log->user->username,
					'action' => $log->action,
					'before' => str_replace(",","\n",$log->before),
					'after' => str_replace(",","\n",$log->after),
					'date' => $format."(".$diff.")",
				)
			);

		}

		return [
			'logsLength' => $logs->total(),
			'logs' => $logs_arr
		];
	}

	public function getcposmsLogs()
	{

		$q = CposmsLogs::query();
		$logs = $this->getLogs($q);

		return response()->json([
			'logsLength' => $logs['logsLength'],
			'logs' => $logs['logs'],
		]);
	}


	public function logPoCreate($po,$itemCount) //create log for creating po
	{

		$log = new CposmsLogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Added PO',
				'before' => '---',
				'after' => $po->po_ponum ." w/ ".$itemCount. " item/s",
			]);

		$log->save();

	}


	public function logPoEdit($dirty,$original,$po)
	{

		$log = new CposmsLogs();
		$name_arr = [
			'po_customer_id' => 'Customer',
			'po_currency' => 'Currency',
			'po_date' => 'Date',
			'po_ponum' => 'PO Number',
		];
		$customer = new Customers();

		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original,'po_customer_id',['model' => $customer, 'cols' => 'companyname']]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited PO '.$po,
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function logPoCancel($po,$remarks,$id)
	{

		$log = new CposmsLogs();
		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Cancelled PO '.$po,
				'before' => '---',
				'after' => 'Remarks : '.$remarks,
			]);

		$log->save();

	}

	public function logPoItemAddAndDelete($po,$item,$method,$id)
	{

		$log = new CposmsLogs();

		if($method == 'Deleted'){
			$before = $item;
			$after = '---';
		}else{
			$before = '---';
			$after = $item;
		}

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method.' item on PO '.$po,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function logPoItemEdit($dirty,$original,$po)
	{

		$log = new CposmsLogs();
		$name_arr = [
			'poi_code' => 'Code',
			'poi_partnum' => 'Part no.',
			'poi_itemdescription' => 'Item description',
			'poi_quantity' => 'Quantity',
			'poi_unit' => 'Unit',
			'poi_unitprice' => 'Unit price',
			'poi_deliverydate' => 'Delivery date',
			'poi_kpi' => 'KPI',
			'poi_others' => 'Others',
			'poi_remarks' => 'Remarks',
		];


		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited item on po '.$po,
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function logPoDeliveredCreateAndDelete($po,$id,$method,$desc,$item)
	{

		$log = new CposmsLogs();

		if($method == 'Deleted'){
			$before = $desc;
			$after = '---';
		}else{
			$before = '---';
			$after = $desc;
		}

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method.' delivery on PO '.$po." ".$item,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function logPoDeliveredEdit($dirty,$original,$po,$item)
	{

		$log = new CposmsLogs();
		$name_arr = [
			'poidel_quantity' => 'Quantity',
			'poidel_underrun_qty' => 'Underrun',
			'poidel_deliverydate' => 'Date',
			'poidel_invoice' => 'Invoice No.',
			'poidel_dr' => 'DR No.',
			'poidel_remarks' => 'Remarks',
		];

		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited delivery on PO '.$po." ".$item,
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();
	}

	public function logPoDeliveryCreateAndDelete($date,$prevCount,$count,$method)
	{
		if($method == 'Deleted'){
			$new = $prevCount - $count;
			$before = $date." (".$prevCount." items)";
			$after = $date." (".$new." items)";;
		}else{
			$new = $prevCount + $count;
			$before = $date." (".$prevCount." items)";
			$after = $date." (".$new." items)";
		}

		$log = new CposmsLogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method." ".$count. " items on delivery schedule",
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function getpjomslogs()
	{

		$logs_arr = array();
		$q = PjomsLogs::query();
		$logs = $this->getLogs($q);

		return response()->json([
			'logsLength' => $logs['logsLength'],
			'logs' => $logs['logs'],
		]);

	}

	public function logJoCreateDelete($method,$po,$item,$jo,$qty,$remarks) //create log for creating po
	{

		$log = new PjomsLogs();
		if($method == 'Added'){
			$action = 'Added job order on po ('.$po.') ('.$item.')';
			$before = '---';
			$after = $jo .", Quantity - ".$qty;
		}else{
			$action = 'Deleted job order on po ('.$po.') ('.$item.') w/ remarks '.$remarks;
			$before = $jo .", Quantity - ".$qty;
			$after = '---';
		}

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $action,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function logJoEdit($dirty,$original,$jo)
	{

		$log = new PjomsLogs();
		$name_arr = [
			'jo_dateissued' => 'Date issued',
			'jo_dateneeded' => 'Date needed',
			'jo_quantity' => 'Quantity',
			'jo_remarks' => 'Remarks',
			'jo_others' => 'Others',
			'jo_forwardToWarehouse' => 'Forward to warehouse'
		];


		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited job order '.$jo,
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function logJoProducedCreateDelete($method,$jo,$qty,$remaining,$date,$remarks)
	{	
		$log = new PjomsLogs();

		$action = $method." ".$qty." produced qty on jo ".$jo;
		$before = 'Remaining : '.$remaining;

		if($method == 'Added'){
			$after = 'Remaining : '.($remaining - $qty).", Date : ".$date.", Remarks : ".$remarks;
		}else{
			
			$after = 'Remaining : '.($remaining + $qty).", Date : ".$date;
		}


		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $action,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function createDeleteLogForMasterlistItem($method,$epcode,$itemdesc,$mspecs)
	{
		$log = new PmmsLogs();

		if($method == 'Added')
		{
			$action = $method." new item ".$epcode;
			$before = '';
			$after = "Item description : ".$itemdesc.", Material specs : ".$mspecs;
		}else {
			$action = $method." item ".$epcode;
			$before = "Item description : ".$itemdesc.", Material specs : ".$mspecs;
			$after = "";
		}

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $action,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function editLogForMasterlistItem($dirty,$original)
	{

		$log = new PmmsLogs();
		$name_arr = [
			'm_moq' => 'Moq',
			'm_mspecs' => 'Material specs',
			'm_projectname' => 'Project name',
			'm_partnumber' => 'Part number',
			'm_code' => 'Code',
			'm_regisdate' => 'Registration date',
			'm_effectdate' => 'Effectivity date',
			'm_requiredquantity' => 'Required qty',
			'm_outs' => 'Outs',
			'm_unit' => 'Unit',
			'm_unitprice' => 'Unit price',
			'm_supplierprice' => 'Supplier price',
			'm_remarks' => 'Remarks',
			'm_customer_id' => 'Customer',
			'm_budgetprice' => 'Budget price',
			'm_product_size' => 'Product Size',
		];
		$customer = new Customers();

		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original,'m_customer_id',['model' => $customer, 'cols' => 'companyname']]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited item',
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function addAndDeleteAttachmentMasterlistItemLog($method,$type,$filename,$ep,$itemdesc)
	{

		if($method === 'Added'){
			$before = '';
			$after = $filename;
		}else{
			$before = $filename;
			$after = '';
		}

		$log = new PmmsLogs();
		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method." ".$type." on ".$ep." - ".$itemdesc,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();

	}

	public function getpmmsLogs()
	{

		$logs_arr = array();
		$q = PmmsLogs::query();
		$logs = $this->getLogs($q);

		return response()->json([
			'logsLength' => $logs['logsLength'],
			'logs' => $logs['logs'],
		]);

	}

	//wims logs
	public function getwimsLogs()
	{

		$q = WimsLogs::query();
		$logs = $this->getLogs($q);

		return response()->json([
			'logsLength' => $logs['logsLength'],
			'logs' => $logs['logs'],
		]);
	}

	public function logAddInventoryItem($code,$spec,$quantity) //create log for creating inventory item
	{

		$log = new Wimslogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Added new item on inventory',
				'before' => '---',
				'after' => $code ." ".$spec. " - ".$quantity,
			]);

		$log->save();

	}

	public function logEditInventoryItem($dirty,$original) //edit inventory log
	{

		$log = new Wimslogs();
		$name_arr = [
			'mspecs' => 'Material specification',
			'itemdesc' => 'Item description',
			'partnum'  => 'Part number',
			'code' => 'Code',
			'unit' => 'Unit',
			'quantity' => 'Quantity',
			'min' => 'Min',
			'max' => 'Max',
		];

		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited item on inventory',
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function logAddIncomingToInventory($code,$spec,$quantity,$newQty) //create log for adding qty to inventory
	{

		$log = new Wimslogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Added incoming '.$quantity.' quantity to '.$code ." - ".$spec,
				'before' => "Old Quantity : ".($newQty - $quantity),
				'after' => "New Quantity : ".$newQty,
			]);

		$log->save();

	}

	public function logDeleteIncomingToInventory($code,$spec,$quantity,$incQty) //create log for deleting incoming to inventory
	{

		$log = new Wimslogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Deleted incoming '.$incQty.' quantity from '.$code ." - ".$spec,
				'before' => "Old Quantity : ".$quantity,
				'after' => "New Quantity : ".($quantity - $incQty),
			]);

		$log->save();

	}

	public function logAddOutgoingToInventory($code,$spec,$quantity,$newQty) //create log for adding qty to inventory
	{

		$log = new Wimslogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Added outgoing '.$quantity.' quantity to '.$code ." - ".$spec,
				'before' => "Old Quantity : ".($newQty + $quantity),
				'after' => "New Quantity : ".$newQty,
			]);

		$log->save();

	}

	public function logDeleteOutgoingToInventory($code,$spec,$quantity,$outQty) //create log for deleting incoming to inventory
	{

		$log = new Wimslogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Deleted outgoing '.$outQty.' quantity from '.$code ." - ".$spec,
				'before' => "Old Quantity : ".$quantity,
				'after' => "New Quantity : ".($quantity + $outQty),
			]);

		$log->save();

	}
	///prms

	public function getprmsLogs()
	{

		$q = PrmsLogs::query();
		$logs = $this->getLogs($q);

		return response()->json([
			'logsLength' => $logs['logsLength'],
			'logs' => $logs['logs'],
		]);
	}

	public function logCreateDeletePrForJo($jo,$pr,$itemCount,$remarks,$method)
	{
		if($method === 'Added'){
			$before = '';
			$after = "W/ ".$itemCount." item/s";
		}else{
			$before = "W/ remarks: ".$remarks;
			$after = '';
		}

		$log = new PrmsLogs();
		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method. " PR no. ".$pr." on ".$jo,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();
	}

	public function logPrEdit($pr,$oldRemarks,$newRemarks){

		$log = new PrmsLogs();
		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited pr '.$pr,
				'before' => "Remarks : ".$oldRemarks,
				'after' => "Remarks : ".$newRemarks,
			]);
		$log->save();

	}

	public function logPrItemEdit($dirty,$original,$pr,$code)
	{

		$log = new PrmsLogs();
		$name_arr = [
			'pri_uom' => 'Unit',
			'pri_quantity' => 'Quantity',
		];

		$vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Edited '.$code.' on pr '.$pr,
				'before' => $vals['before'],
				'after' => $vals['after'],
			]);

		$log->save();

	}

	public function logCreateDeletePrItem($pr,$epcode,$method)
	{
		if($method === 'Added'){
			$before = '';
			$after = $epcode;
		}else{
			$before = $epcode;
			$after = '';
		}

		$log = new PrmsLogs();
		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => $method." item on ".$pr,
				'before' => $before,
				'after' => $after,
			]);

		$log->save();
	}

  // psms
  public function logCreateAndRemovePriceToPr($pr, $supplier, $method = 'Added') {
    $before = '';
    $after = 'Supplier : '.$supplier;

    if ($method == 'Deleted') {
      $before = "Supplier : ".$supplier;
      $after = '';
    }

    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => $method." price for pr ".$pr,
        'before' => $before,
        'after' => $after,
      ]);

    $log->save();
  }

  public function logPrSupplierDetailsEdit($pr, $dirty,$original)
  {

    $log = new PsmsLogs();
    $name_arr = [
      'prsd_supplier_id' => 'Supplier',
      'prsd_currency' => 'Currency',
    ];
    $supplier = new Supplier();

    $vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original,'po_customer_id',['model' => $supplier, 'cols' => 'sd_supplier_name']]);

    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => 'Edited supplier details on '.$pr,
        'before' => $vals['before'],
        'after' => $vals['after'],
      ]);

    $log->save();
  }

  public function logPrSupplierDetailsItemEdit($pr,$dirty,$original){
    $log = new PsmsLogs();
    $name_arr = [
      'pri_unitprice' => 'Unit price',
      'pri_deliverydate' => 'Delivery date',
    ];

    $vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => 'Edited supplier detail items on '.$pr,
        'before' => $vals['before'],
        'after' => $vals['after'],
      ]);

    $log->save();
  }

  public function logCreateAndRemovalOfApprovalRequest($pr, $requestType, $method){
    $before = '';
    $after = $pr;

    if ($method == 'Deleted') {
      $before = $pr;
      $after = '';
    }

    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => $method." request for approval via ".$requestType,
        'before' => $before,
        'after' => $after,
      ]);

    $log->save();
  }

  public function logCreateAndRemovalOfPotoPr($prs, $po, $method){
    $before = '';
    $after = 'Purchase request ('.$prs.')';

    if ($method == 'Deleted') {
      $before = 'Purchase request ('.$prs.')';
      $after = '';
    }

    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => $method." purchase order ".$po,
        'before' => $before,
        'after' => $after,
      ]);

    $log->save();
  }

  public function logSentPOToSupplier($po, $status) {
    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => "Marked purchase order ".$po." as sent to supplier",
        'before' => "",
        'after' => "Status: ".$status,
      ]);

    $log->save();
  }

  public function logAddRemovedDeliveredToPo($po, $poitem, $invoice, $dr, $qty, $method) {
    $before = '';
    $after = 'Invoice: '.$invoice.' / D.R. : '.$dr." / Qty. : ".$qty;

    if ($method == 'Deleted') {
      $before = 'Invoice: '.$invoice.' / D.R. : '.$dr." / Qty. : ".$qty;
      $after = '';
    }

    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => $method." delivery details to ".$poitem." on ".$po,
        'before' => $before,
        'after' => $after,
      ]);

    $log->save();
  }

  public function logEditedDeliveredToPo($po,$poitem,$dirty,$original){
    $log = new PsmsLogs();
    $name_arr = [
      'ssi_invoice' => 'Invoice',
      'ssi_dr' => 'Delivery receipt',
      'ssi_date' => 'Date',
      'ssi_drquantity' => 'Quantity',
      'ssi_underrunquantity' => 'Underrun Quantity',
    ];

    $vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => "Edited delivery details to ".$poitem." on ".$po,
        'before' => $vals['before'],
        'after' => $vals['after'],
      ]);

    $log->save();
  }

  public function logAddRemovedSupplier($supplier, $method) {
    $before = '';
    $after = $supplier;

    if ($method == 'Deleted') {
      $before = $supplier;
      $after = '';
    }

    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => $method." Supplier",
        'before' => $before,
        'after' => $after,
      ]);

    $log->save();
  }

  public function logEditedSupplier($supplier,$dirty,$original){
    $log = new PsmsLogs();
    $name_arr = [
      'sd_supplier_name' => 'Supplier name',
      'sd_address' => 'Address',
      'sd_tin' => 'Tin',
      'sd_attention' => 'Attention to',
      'sd_paymentterms' => 'Payment terms',
    ];

    $vals = $this->getBeforeAndAfter([$name_arr,$dirty,$original]);

    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => "Edited Supplier",
        'before' => $vals['before'],
        'after' => $vals['after'],
      ]);

    $log->save();
  }

  public function logCreatingReceivedReport($po, $rrNum, $item) {
    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => "Added Received Report No. ".$rrNum,
        'before' => "Purchase order: ".$po." / Item: ".$item,
        'after' => "",
      ]);

    $log->save();
  }

  public function logDeleteingReceivedReport($rrNum, $po){
    $log = new PsmsLogs();
    $log->fill(
      [
        'user_id' => auth()->user()->id,
        'action' => "Deleted Received Report No. ".$rrNum,
        'before' => "Purchase order: ".$po,
        'after' => "",
      ]);

    $log->save();
  }

  public function getpsmsLogs()
  {

    $q = PsmsLogs::query();
    $logs = $this->getLogs($q);

    return response()->json([
      'logsLength' => $logs['logsLength'],
      'logs' => $logs['logs'],
    ]);
  }

  //end psms


}
