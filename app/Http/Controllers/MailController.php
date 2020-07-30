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

  public function getEmailReceiptients()
  {
    $email = EmailListCposms::all();

    return response()->json([
      'emailReceiptients' => $email,
    ]);
  }

  public function addEmail(Request $request)
  {
    $validator  = Validator::make($request->all(),[
      'email' => 'email|required|unique:cposms_mail,email'
    ]);

    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()],422);

    $email = EmailListCposms::create(['email' => $request->email]);

    return response()->json([
      'message' => 'Email successfully added',
      'newEmail' => $request->email,
    ]);
  }

  public function deleteEmail($id)
  { 
    EmailListCposms::findOrFail($id)->delete();

    return response()->json([
      'message' => 'Email deleted',
    ]);
  }

	public function sendEmailSchedule(Request $request)
	{
		$validator = Validator::make($request->all(),
			[
				'dates' => 'array|required|min:1',
				'cc' => 'array',
				'bcc' => 'array',
				'recipients' => 'array|required|min:1',
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
    $fullname = auth()->user()->firstname." ".auth()->user()->lastname;
		$mail->send(new PoItemScheduleMail($sched_arr,$dates,$fullname));

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
			'customerLabel' => 'string|required',
      'po_num' => 'string|required',
			'date' => 'date|required',
		],[],['customerLabel' => 'customer']);

		if($validator->fails()){
			return response()->json(['errors' => $validator->errors()->all()],422);
		}

		$emails = EmailListCposms::all()->pluck('email')->toArray();
		$po = PurchaseOrder::find($request->id);
    $poDetails = (object) array(
      'customer' => $request->customerLabel,
      'po_num' => $request->po_num,
      'date' => $request->date,
      'itemCount' => $po->poitems()->count(),
      'items' => $po->poitems->map(function($item){
        return array(
          'code' => $item->poi_code,
          'partnum' => $item->poi_partnum,
          'itemdesc' => $item->poi_itemdescription,
          'quantity' => $item->poi_quantity,
          'unit' => $item->poi_unit,
          'deliverydate' => $item->poi_deliverydate
        );
      })
    );
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
    $company = request()->company ?? 'COMPANY NOT DEFINED';
		Mail::to($emails)
		  ->send(new PoEmailNotification($poDetails, $company));

		return response()->json(
			[
				'id' => $request->id,
				'message' => 'Endorsement email sent'
			]);

		
	}

}
