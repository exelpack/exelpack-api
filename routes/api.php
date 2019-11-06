<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login/{sys}','UserController@login');

Route::get('/error', function(){
	return response()->json(['error' => 'Unauthorized'],401);
})->name('unauthenticated');



Route::group(['middleware' => ['auth:api']], function() {
	Route::post('/logout/{sys}','UserController@logout');
	Route::post('/me','UserController@me');
	Route::get('/test', 'PurchaseOrderController@test');

	Route::group(['middleware' => ['cposms']], function() {
		Route::get('/cposms/logs', 'LogsController@getcposmsLogs');

			// cposms
		Route::get('/cposms/option/poselect', 'PurchaseOrderController@getOptionsPOSelect'); // fetch option for po
		Route::get('/cposms/option/openitems', 'PurchaseOrderController@getOpenItems'); //fetch all open items

		Route::post('/cposms/po','PurchaseOrderController@createPurchaseOrder'); //add po
		Route::put('/cposms/po/{id}','PurchaseOrderController@editPurchaseOrder'); //edit po
		Route::get('/cposms/po','PurchaseOrderController@poIndex'); //fetch po
		Route::delete('/cposms/po/{id}','PurchaseOrderController@cancelPo'); //cancel po
		Route::get('/cposms/poitems','PurchaseOrderController@poItemsIndex'); //fetch po items

		// delivery
		Route::get('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@fetchItemDelivery');
		Route::post('/cposms/poitems/delivery', 'PurchaseOrderController@addDelivery');
		Route::put('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@editDelivery');
		Route::delete('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@deleteDelivery')->middleware('checkPrivelege');
		Route::get('/cposms/poitems/delivery', 'PurchaseOrderController@fetchDeliveries');

		Route::get('/cposms/poitems/schedules/{id}', 'PurchaseOrderController@getPoItemSchedule');
		Route::get('/cposms/poitems/schedules', 'PurchaseOrderController@getMonthItemCountSchedule');
		Route::get('/cposms/poitems/schedules/{date}/item', 'PurchaseOrderController@getDailySchedules');
		Route::post('/cposms/poitems/schedules', 'PurchaseOrderController@addDailySchedule');
		Route::put('/cposms/poitems/schedules/{id}', 'PurchaseOrderController@updateItemSchedule');
		Route::delete('/cposms/poitems/schedules/{ids}', 'PurchaseOrderController@deleteItemSchedule');

		//exports
		Route::get('/cposms/po/export-csv','PurchaseOrderController@exportPoCsv');
		Route::get('/cposms/poitems/export-csv','PurchaseOrderController@exportPoItemsCsv');
		Route::get('/cposms/poitems/schedules/export-csv/dl','PurchaseOrderController@exportPoDailySchedule');
		Route::get('/cposms/poitems/delivery/export-csv/dl','PurchaseOrderController@exportPoDelivered');
		Route::get('/cposms/poitems/schedules/export-pdf/dl','PurchaseOrderController@exportPoDailyScheduleToPDF');
		// end cposms

	});

	Route::group(['middleware' => ['pjoms']], function() {

		Route::get('pjoms/option/openitems', 'JobOrderController@getOpenItems');
		
	});

});
