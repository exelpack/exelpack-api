<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\PoItemScheduleMail;
use Illuminate\Support\Facades\Validator;

use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\PurchaseOrderDelivery;
use App\PurchaseOrderSchedule;
use App\Masterlist;
use App\Customers;

use DB;
use Mail;
use Carbon\Carbon;
class MailController extends Controller
{

	public function sendEmailSchedule(Request $request)
	{
		$validator = Validator::make($request->all(),
			[
				'dates' => 'array|required',
				'cc' => 'array',
				'bcc' => 'array',
				'recipients' => 'array|required',
			]);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$schedules = PurchaseOrderSchedule::whereIn('pods_scheduledate', $request->dates)
		->orderBy('pods_scheduledate','asc')
		->get();
		$sched_arr = array();
		foreach($schedules as $sched){
			array_push($sched_arr,[
				'customer' => $sched->item->po->customer->companyname,
				'po' => $sched->item->po->po_ponum,
				'itemdesc' => $sched->item->poi_itemdescription,
				'date' => $sched->pods_scheduledate,
				'quantity' => $sched->pods_quantity,
				'remaining' => $sched->pods_remaining,
				'remarks' => $sched->pods_remarks,
				'jo' => implode(",",$sched->item->jo->pluck('jo_joborder')->toArray())
			]);

		}
		$dates = "Purchase order delivery schedule for ".implode(',',$request->dates);
		$failures = "";

		$mail = Mail::to($request->recipients);

		if(count($request->cc) > 0)
			$mail->cc($request->cc);
		
		if(count($request->bcc) > 0)
			$mail->bcc($request->bcc);

		$mail->send(new PoItemScheduleMail($sched_arr,$dates));

		return response()->json([
			'message' => 'Email sent'
		]);
	}

}
