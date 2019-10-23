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
Route::get('/test', 'PurchaseOrderController@test');
Route::post('/login/{sys}','UserController@login');

Route::get('/error', function(){
	return response()->json(['error' => 'Unauthorized'],401);
})->name('unauthenticated');



Route::group(['middleware' => ['auth:api']], function() {
	Route::post('/logout','UserController@logout');
	Route::post('/me','UserController@me');

	Route::get('/cposms/option/poselect', 'PurchaseOrderController@getOptionsPOSelect'); // fetch option for po
	Route::get('/cposms/option/openitems', 'PurchaseOrderController@getOpenItems'); //fetch all open items

	Route::post('/cposms/po','PurchaseOrderController@createPurchaseOrder'); //add po
	Route::put('/cposms/po','PurchaseOrderController@editPurchaseOrder'); //edit po
	Route::get('/cposms/po','PurchaseOrderController@poIndex'); //fetch po
	Route::delete('/cposms/po/{id}','PurchaseOrderController@cancelPo'); //cancel po

	Route::get('/cposms/poitems','PurchaseOrderController@poItemsIndex'); //fetch po items

	// delivery
	Route::get('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@fetchItemDelivery');
	Route::post('/cposms/poitems/delivery', 'PurchaseOrderController@addDelivery');
	Route::put('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@editDelivery');
	Route::delete('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@deleteDelivery');
	Route::get('/cposms/poitems/delivery', 'PurchaseOrderController@fetchDeliveries');

	Route::get('/cposms/poitems/schedules', 'PurchaseOrderController@getMonthItemCountSchedule');
	Route::get('/cposms/poitems/schedules/{date}', 'PurchaseOrderController@getDailySchedules');


});
