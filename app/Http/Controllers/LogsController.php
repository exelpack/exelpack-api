<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CposmsLogs;
use App\Customers;
use Illuminate\Support\Facades\Auth;

class LogsController extends Controller
{

	public function getLogs()
	{



	}


	public function logPoCreate($po,$itemCount) //create log for creating po
	{

		$log = new CposmsLogs();

		$log->fill(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Created PO',
				'before' => '---',
				'after' => $po->po_ponum ." w/ ".$itemCount. " item/s",
				'owner_id' => $po->id,
				'class' => 'PO'
			]);

		$log->save();

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

			if($key == $details[3]){
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
				'owner_id' => $original['id'],
				'class' => 'PO'
			]);

		$log->save();

	}


}
