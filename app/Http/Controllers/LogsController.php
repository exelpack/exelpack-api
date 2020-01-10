<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Units;
use App\CposmsLogs;
use App\PjomsLogs;
use App\PmmsLogs;
use App\WimsLogs;
use App\Customers;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{
	public function getUnits()
	{
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


	public function logPoEdit($dirty,$original)
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
				'action' => 'Edited PO',
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
			'unitprice' => 'Unit price',
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

	public function logCreatePrForJo($jo,$pr,$itemCount)
	{
		
	}

}
