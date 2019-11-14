<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\PoItemScheduleMail;
use App\Mail\PoEmailNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\PurchaseOrder;
use App\PurchaseOrderItems;
use App\PurchaseOrderDelivery;
use App\PurchaseOrderSchedule;
use App\Masterlist;
use App\Customers;
use App\EmailListCposms;
use App\CposmsLogs;

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
		$dates = "Purchase order delivery schedule";
		$failures = "";

		$mail = Mail::to($request->recipients);

		if(count($request->cc) > 0)
			$mail->cc($request->cc);
		
		if(count($request->bcc) > 0)
			$mail->bcc($request->bcc);

		// return (new PoItemScheduleMail($sched_arr,$dates,auth()->user()->username))->render();
		$mail->send(new PoItemScheduleMail($sched_arr,$dates));

		$log = new CposmsLogs();

		$log->create(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Sent po delivery schedule dates for ('.implode(',',$request->dates).")",
				'before' => '---',
				'after' => '---',
			]);

		return response()->json([
			'message' => 'Email sent'
		]);
	}

	public function endorsePo(Request $request){

		$validator = Validator::make($request->all(),[
			'customer_label' => 'string|required',
			'date' => 'date|required',
			'items' => 'array|min:1'
		],[],['customer_label' => 'customer']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$poDetails = array(
			'customer' => $request->customer_label,
			'po_num' => $request->po_num,
			'date' => $request->date,
			'itemCount' => count($request->items),
			'items' => $request->items
		);

		$emails = EmailListCposms::all()->pluck('email')->toArray();
		$po = PurchaseOrder::find($request->id);
		if($po->isEndorsed)
			return response()->json(['errors' => ['Purchase order already sent to planner']],422);

		$po->update(['isEndorsed' => 1]);

		$log = new CposmsLogs();

		$log->create(
			[
				'user_id' => auth()->user()->id,
				'action' => 'Endorsed purchase order '.$po->po_ponum,
				'before' => '---',
				'after' => '---',
			]);

		Mail::to($emails)
		->send(new PoEmailNotification($poDetails));

		return response()->json(
			[
				'id' => $request->id,
				'message' => 'Endorsement email sent'
			]);

		
	}

}
