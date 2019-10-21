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


Route::get('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@fetchItemDelivery');
Route::post('/cposms/poitems/delivery', 'PurchaseOrderController@addDelivery');
Route::put('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@editDelivery');
Route::delete('/cposms/poitems/delivery/{id}', 'PurchaseOrderController@deleteDelivery');
Route::get('/cposms/poitems/delivery', 'PurchaseOrderController@fetchDeliveries');

Route::group(['middleware' => ['auth:api']], function() {
	Route::post('/logout','UserController@logout');

	Route::get('/cposms/option/poselect', 'PurchaseOrderController@getOptionsPOSelect'); // fetch option for po
	Route::post('/cposms/po','PurchaseOrderController@createPurchaseOrder'); //add po
	Route::put('/cposms/po','PurchaseOrderController@editPurchaseOrder'); //edit po
	Route::get('/cposms/po','PurchaseOrderController@poIndex'); //fetch po
	Route::delete('/cposms/po/{id}','PurchaseOrderController@cancelPo'); //cancel po

	Route::get('/cposms/poitems','PurchaseOrderController@poItemsIndex'); //fetch po items


});
