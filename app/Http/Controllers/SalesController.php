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
class SalesController extends Controller
{

	public function getCustomers()
	{

		$customers = SalesCustomer::all()
									->map(function($customer) {
										return array(
											'id' => $customer->id,
											'customer_name' => $customer->c_customername,
										);
									});

		return response()->json(
			[
				'customersList' => $customers
			]);

	}
  
	public function getSales()
	{

		$sales = SalesInvoice::orderBy('s_invoicenum','DESC')
        ->get()
        ->map(function($invoice){	
        	$status = 'Not Collected';

        	if($invoice->s_ornumber && $invoice->s_datecollected)
        		$status = 'Collected';

        	return array(
            'id' => $row->id,
            'status' => $status,
            'invoice_num' => $invoice->id,
            'delivery_date' => $invoice->delivery_date,
            'customer_id' => $invoice->customer->id,
            'customer_name' => $invoice->customer->c_customername,
            'payment_terms' => $invoice->customer->c_paymentterms,
            'withholdingtax' => $invoice->s_withholding,
            'or_num' => $invoice->s_ornumber,
            'date_collected' => $invoice->s_datecollected,
            );

        });

    return response()->json(
    	[
    		'salesList' => $sales,
    	]);

	}

	// 'dr' => $row->dr_num,
 //            'po' => $row->po_num,
 //            'partnum' => $row->part_num,
 //            'qty' => $row->quantity,
 //            'currency' => $row->sales->currency,
 //            'unitprice' => $row->unitprice,
 //            'total_amount' => $row->total_amount,

}
