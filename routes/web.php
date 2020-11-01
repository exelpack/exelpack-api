<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// ini_set('max_execution_time', 100000);
// Route::get('/test', function() {
// 	$list = Db::table('test')->get();
// 	foreach($list as $key => $row){
// 		$id = $key + 1;
// 		$supplier = Db::table('purchasesms_supplier')->where('supplier_name', $row->three)->first();
// 		$account = Db::table('purchasesms_accounts')->where('accounts_code', $row->four)->first();

// 		$date1 = Carbon::parse($row->one)->format('Y-m-d');
// 		$date2 = Carbon::parse($row->two)->format('Y-m-d');

// 		Db::table('purchasesms_items')->insert([
// 			'item_datereceived' => $date1,
// 			'item_datepurchased' => $date2,
// 			'item_supplier_id' => $supplier->id,
// 			'item_accounts_id' => $account->id,
// 			'item_salesinvoice_no' => $row->five,
// 			'item_deliveryreceipt_no' => $row->six,
// 			'item_purchaseorder_no' => $row->seven,
// 			'item_purchaserequest_no' => $row->eight,
// 			'item_particular' => $row->nine,
// 			'item_quantity' => $row->ten,
// 			'item_unit' => $row->eleven,
// 			'item_with_unreleasedcheck' => $row->twelve !== "" ? 1 : 0,
// 			'item_currency' => $row->thirteen !== "" ? "PHP" : "USD",
// 			'item_unitprice' => $row->fourteen,
// 		]);
// 		$withAP = 'AP NONE';
// 		if($row->d !== ''){
// 			$date3 = Carbon::parse($row->d)->format('Y-m-d');
// 			Db::table('purchasesms_apdetails')->insert([
// 				'ap_item_id' => $id,
// 				'ap_withholding' => $row->a,
// 				'ap_officialreceipt_no' => $row->b,
// 				'ap_is_check' => $row->c !== '' ? 1 : 0,
// 				'ap_check_no' => $row->c,
// 				'ap_bankname' => '',
// 				'ap_payment_date' => $date3,
// 			]);
// 			$withAP = "WITH AP";
// 		}
		
// 		echo $row->id." - DONE - ".$withAP."<br/>";
// 	}
// });

// $list = Db::table('test')->where('two','=',NULL)->get();

	// foreach($list as $row) {
	// 	$item = DB::table('purchasesms_items')->where('id', $row->id)->update([
	// 		'item_unitprice' => $row->one,
	// 	]);
	// 	$testing = Db::table('test')->where('id', $row->id)->update([
	// 		'two' => 'done',
	// 	]);
	// 	echo $row->id."</br>";
	// }
	
	

	// $list = Db::table('test')->groupBy('one')->get();
	
	// foreach($list as $row) {
	// 	Db::table('purchasesms_supplier')->insert([
	// 		'supplier_name' => $row->one,
	// 		'supplier_payment_terms' => $row->two,
	// 		'supplier_address' => $row->three,
	// 		'supplier_tin_number' => $row->four,
	// 	]);
	// }

	// $list = Db::table('test')->groupBy('one')->get();
	// foreach($list as $row) {
	// 	// echo $row->one."<br/>";
	// 	Db::table('purchasesms_accounts')->insert([
	// 		'accounts_code' => $row->one,
	// 		'accounts_name' => $row->one,
	// 	]);
	// }