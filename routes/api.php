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
Route::get('/options/units','LogsController@getUnits');

Route::group(['middleware' => ['auth:api']], function() {
	Route::post('/logout/{sys}','UserController@logout');
	Route::post('/me','UserController@me');
  Route::get('/pmms/masterlist','MasterlistController@getMasterlist');
  Route::get('/pmms/masterlist/attachment/{id}/{type}','MasterlistController@downloadAttachment');
  
  // PurchasesSupplierController@printPurchaseOrder
	// ['error' => 'Unauthorized'],

	Route::group(['middleware' => ['cposms']], function() {

    //mail
    Route::get('/cposms/emails', 'MailController@getEmailReceiptients');
    Route::post('/cposms/emails', 'MailController@addEmail');
    Route::delete('/cposms/emails/{id}', 'MailController@deleteEmail');

		Route::get('/cposms/logs', 'LogsController@getcposmsLogs');
		Route::get('/cposms/poitems/overall/{id}', 'PurchaseOrderController@getItemOverallDetails');

			// cposms
    Route::get('/cposms/option/poselect', 'PurchaseOrderController@getOptionsPOSelect'); // fetch option for po
		Route::get('/cposms/option/customeritems', 'PurchaseOrderController@getCustomerItemsOptions'); // fetch option for po
		Route::get('/cposms/option/openitems', 'PurchaseOrderController@getOpenItems'); //fetch all open items
		Route::get('/cposms/option/scheduledates', 'PurchaseOrderController@getScheduleDates'); //fetch all open items

		Route::post('/cposms/po','PurchaseOrderController@createPurchaseOrder'); //add po
    Route::put('/cposms/po/{id}','PurchaseOrderController@editPurchaseOrder'); //edit po
		Route::get('/cposms/po/{id}','PurchaseOrderController@getPoItems'); //edit po
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
		Route::get('/cposms/po/export-csv/dl','PurchaseOrderController@exportPoCsv');
		Route::get('/cposms/poitems/export-csv/dl','PurchaseOrderController@exportPoItemsCsv');
		Route::get('/cposms/poitems/schedules/export-csv/dl','PurchaseOrderController@exportPoDailySchedule');
		Route::get('/cposms/poitems/delivery/export-csv/dl','PurchaseOrderController@exportPoDelivered');
		Route::get('/cposms/poitems/schedules/export-pdf/dl','PurchaseOrderController@exportPoDailyScheduleToPDF');

		Route::get('/cposms/po/reporting/sales','PurchaseOrderController@salesReport');
		Route::get('/cposms/po/reporting/export-sales/dl','PurchaseOrderController@exportSales');
		// end cposms

		Route::post('/mail/schedule', 'MailController@sendEmailSchedule');
		Route::post('/mail/endorsepo', 'MailController@endorsePo');

    //custoer
    Route::get('/cposms/customers', 'CustomerController@getCustomers');
    Route::post('/cposms/customers', 'CustomerController@addCustomer');
    Route::put('/cposms/customers/{id}', 'CustomerController@updateCustomer');
    Route::delete('/cposms/customers/{id}', 'CustomerController@deleteCustomer');

	});

	Route::group(['middleware' => ['pjoms']], function() {
		Route::get('/pjoms/logs', 'LogsController@getpjomslogs');

		Route::get('/pjoms/option/openitems', 'JobOrderController@getOpenItems');
		Route::get('/pjoms/option/series', 'JobOrderController@fetchJoSeries');
		Route::post('/pjoms/jo', 'JobOrderController@createJo');
		Route::put('/pjoms/jo/{id}', 'JobOrderController@updateJo');
		Route::get('/pjoms/jo', 'JobOrderController@fetchJo');
		Route::delete('/pjoms/jo/{id}', 'JobOrderController@deleteJo');

		Route::get('/pjoms/jo/produced/{id}','JobOrderController@getJoProducedQty');
		Route::post('/pjoms/jo/produced','JobOrderController@addJoProduced');
		Route::post('/pjoms/jo/produced/{id}','JobOrderController@closeJobOrder');
		Route::delete('/pjoms/jo/produced/{id}','JobOrderController@deleteJoProduced');

		Route::get('/pjoms/jo/itemdetails/{id}', 'JobOrderController@getItemDetails');
		//exports
		Route::get('/pjoms/jo/export-csv/dl','JobOrderController@exportJobOrder');
		Route::get('/pjoms/jo/print','JobOrderController@printJobOrder');
		
	});

	Route::group(['middleware' => ['pmms']], function() {
		Route::get('/pmms/logs','LogsController@getpmmsLogs');

		Route::get('/pmms/masterlist/option/customers','MasterlistController@getCustomerList');

		Route::post('/pmms/masterlist', 'MasterlistController@addItem');
		Route::put('/pmms/masterlist/{id}', 'MasterlistController@editItem');
		Route::delete('/pmms/masterlist/{id}','MasterlistController@deleteItem');

		Route::post('/pmms/masterlist/attachment','MasterlistController@addAttachmentToItem');
		Route::put('/pmms/masterlist/attachment/{id}','MasterlistController@setAttachmentViewability');
		Route::delete('/pmms/masterlist/attachment/{id}','MasterlistController@deleteAttachment');
		
		//export
		Route::get('/pmms/masterlist/export', 'MasterlistController@exportMasterlist');

	});

	//wims
	Route::group(['middleware' => ['wims']], function() {

		Route::get('/inventory/logs', 'LogsController@getwimsLogs');

		Route::get('/inventory/option/masterlist', 'InventoryController@getMasterlistItems');
		Route::get('/inventory/option/joborder', 'InventoryController@getJobOrders');

    Route::get('/inventory', 'InventoryController@getInventoryItems');
		Route::get('/inventory/export', 'InventoryController@exportInventory');
		Route::post('/inventory', 'InventoryController@createInvetoryItem');
		Route::put('/inventory/{id}', 'InventoryController@editInventoryItem');
		Route::delete('/inventory/{id}', 'InventoryController@deleteInventoryItem');

		Route::post('/inventory/incoming','InventoryController@createInventoryIncoming');
		Route::get('/inventory/incoming','InventoryController@getInventoryIncoming');
		Route::delete('/inventory/incoming/{id}','InventoryController@deleteIncoming');

		Route::get('/inventory/outgoing','InventoryController@getInventoryOutgoing');
		Route::post('/inventory/outgoing','InventoryController@createInventoryOutgoing');
		Route::delete('/inventory/outgoing/{id}','InventoryController@deleteOutgoing');

		Route::get('/inventory/locations', 'InventoryController@getLocations');
    Route::post('/inventory/locations', 'InventoryController@addLocation');
    Route::post('/inventory/locations/map', 'InventoryController@updateLocationMapDetails');
    Route::put('/inventory/locations/{id}', 'InventoryController@updateLocation');
    Route::delete('/inventory/locations/{id}', 'InventoryController@deleteLocation');

		Route::post('/inventory/item/locations', 'InventoryController@addLocationToInventory');
		Route::delete('/inventory/item/locations/{id}', 'InventoryController@removeLocationToInventory');
		//prms(wh)
		Route::get('/prms/jolist', 'PurchaseRequestController@getJobOrders');
    Route::get('/prms/pr/{id}', 'PurchaseRequestController@getPrItemDetails');
		Route::get('/prms/pr/item/{id}', 'PurchaseRequestController@getPrItemDeliveryAndIssuance');
		Route::get('/prms/pr', 'PurchaseRequestController@getPrList');
		Route::post('/prms/pr', 'PurchaseRequestController@addPr');
		Route::put('/prms/pr/{id}', 'PurchaseRequestController@editPr');
		Route::delete('/prms/pr/{id}', 'PurchaseRequestController@deletePr');

		Route::get('/prms/logs', 'PurchaseRequestController@getprmsLogs');

    //po receiving
    Route::get('/wrms/polist', 'ReceivingController@getPurchaseOrder');
    Route::get('/wrms/polist/{id}', 'ReceivingController@viewPurchaseOrderItems');
    Route::get('/wrms/invoices/{id}', 'ReceivingController@getItemInvoices');
    Route::post('/wrms/invoices', 'ReceivingController@addReceivingReport');
    Route::get('/wrms/receivingreport', 'ReceivingController@getRRList');
    Route::delete('/wrms/receivingreport/{id}', 'ReceivingController@removeRRfromInvoice');

    Route::get('/wrms/receivingreport/exports/{id}', 'ReceivingController@printRR');
    Route::get('/wrms/rtv/exports/{id}', 'ReceivingController@printRTV');

	});

		//sales
	Route::group(['middleware' => ['salesms']], function() {

    Route::get('/salesms/invoice','SalesController@getSales');
		Route::get('/salesms/invoice/{customerId}','SalesController@getInvoicesForCustomer');
    Route::get('/salesms/customer','SalesController@getCustomers');
    Route::get('/salesms/export/soa','SalesController@exportSoa');
    Route::get('/salesms/export/sales','SalesController@exportSales');
    Route::get('/salesms/export/summary','SalesController@exportSalesSummary');
    Route::get('/salesms/export/summaryExt','SalesController@exportSalesSummaryExternal');
    Route::get('/salesms/export/ar','SalesController@exportAR');
    Route::get('/salesms/export/crb','SalesController@exportCRB');
    Route::get('/salesms/or','SalesController@searchOR');

    Route::post('/salesms/invoice','SalesController@createSales');
    Route::post('/salesms/customer','SalesController@addCustomer');

    Route::put('/salesms/invoice/{id}','SalesController@updateSales');
    Route::put('/salesms/customer/{id}','SalesController@updateCustomer');

    Route::put('/salesms/invoice','SalesController@markInvoicesCollected');

    Route::delete('/salesms/customer/{id}','SalesController@deleteCustomer');
    Route::delete('/salesms/invoice/{id}/revised','SalesController@reviseSales');
    Route::delete('/salesms/invoice/{id}','SalesController@deleteSales');

	});

  Route::group(['middleware' => ['psms']], function() {

    Route::get('/psms/approval/{id}','PurchasesSupplierController@getApprovalList');
    // Route::get('/psms/supplier','PurchasesSupplierController@getSupplier');
    Route::get('/psms/pr','PurchasesSupplierController@getPrList');
    Route::get('/psms/prprice','PurchasesSupplierController@getPrListWithPrice');
    Route::get('/psms/pr/{id}','PurchasesSupplierController@getPrInfo');
    Route::get('/psms/pr/{prId}/{supplierId}','PurchasesSupplierController@getPriceForItems');
    Route::get('/psms/print/{id}','PurchasesSupplierController@printPR');
    Route::get('/storage/signature','PurchasesSupplierController@getFileSignature');
    Route::get('/psms/po','PurchasesSupplierController@getPurchaseOrder');
    Route::get('/psms/po/{id}','PurchasesSupplierController@purchaseOrderInfo');
    Route::get('/psms/poitem','PurchasesSupplierController@getPoItems');
    Route::get('/psms/po/print/{id}','PurchasesSupplierController@printPurchaseOrder');
    Route::get('/psms/po/invoice/{id}','PurchasesSupplierController@getPurchaseOrderDeliveries');
    Route::get('/psms/supplier','PurchasesSupplierController@getAllSupplier');


    Route::post('/psms/pr','PurchasesSupplierController@addPriceForItems');
    Route::post('/psms/approval','PurchasesSupplierController@addApprovalRequest');
    Route::post('/psms/po/invoice','PurchasesSupplierController@addDeliveryToPO');
    Route::post('/psms/supplier','PurchasesSupplierController@addSupplier');
    Route::post('/psms/purchasesreport', 'PurchasesSupplierController@getPurchasesReport');
    Route::post('/psms/purchasesreport/exports/dl', 'PurchasesSupplierController@exportPurchasesReport');
    
    Route::put('/psms/pr/{id}','PurchasesSupplierController@editPrWithPrice');
    Route::put('/psms/po/{id}','PurchasesSupplierController@markAsSentToSupplier');
    Route::put('/psms/po/invoice/{id}','PurchasesSupplierController@updateDeliveryToPo');
    Route::put('/psms/supplier/{id}','PurchasesSupplierController@updateSupplier');

    Route::delete('/psms/approval/{id}','PurchasesSupplierController@deleteApprovalRequest');
    Route::delete('/psms/pr/{id}','PurchasesSupplierController@deletePriceOnPr');
    Route::delete('/psms/supplier/{id}','PurchasesSupplierController@deleteSupplier');

    Route::post('/psms/po/preview','PurchasesSupplierController@getAllDetailsForPr');
    Route::post('/psms/po','PurchasesSupplierController@addPurchaseOrder');

    Route::delete('/psms/po/{id}','PurchasesSupplierController@cancelPurchaseOrder');
    Route::delete('/psms/po/invoice/{id}','PurchasesSupplierController@deleteDeliveryPo');

    //exports
    Route::get('/psms/po/exports/dl', 'PurchasesSupplierController@exportPurchaseOrder');
    Route::get('/psms/poitem/exports/dl', 'PurchasesSupplierController@exportPurchaseOrderItems');

    Route::get('/psms/logs', 'PurchaseRequestController@getpsmsLogs');

    
  });

  Route::group(['middleware' => ['purchasesms']], function() {

    Route::get('/purchasesms/items','PurchasesController@getItems');
    Route::post('/purchasesms/items','PurchasesController@addItem');
    Route::put('/purchasesms/items/{id}','PurchasesController@updatedItem');
    Route::delete('/purchasesms/items/{id}','PurchasesController@deleteItem');

    Route::get('/purchasesms/suppliers','PurchasesController@getSuppliers');
    Route::post('/purchasesms/suppliers','PurchasesController@addSupplier');
    Route::put('/purchasesms/suppliers/{id}','PurchasesController@updateSupplier');
    Route::delete('/purchasesms/suppliers/{id}','PurchasesController@deleteSupplier');

    Route::get('/purchasesms/accounts','PurchasesController@getAccounts');
    Route::post('/purchasesms/accounts','PurchasesController@addAccount');
    Route::put('/purchasesms/accounts/{id}','PurchasesController@updateAccount');
    Route::delete('/purchasesms/accounts/{id}','PurchasesController@deleteAccount');

    Route::get('/purchasesms/suppliers/items/{id}','PurchasesController@getItemsBySupplier');
    Route::post('/purchasesms/ap','PurchasesController@addPayment');

    Route::get('/purchasesms/exports/bir','PurchasesController@exportBirMonthly');
    Route::get('/purchasesms/exports/payables','PurchasesController@exportPayablesReport');
    Route::get('/purchasesms/exports/purchases','PurchasesController@exportPurchasesReport');
    Route::get('/purchasesms/exports/ap','PurchasesController@exportAccountsPayablesSummary');

  });

  Route::group(['middleware' => ['approvalpr']], function() {
    Route::get('/odash/prapproval','OperationController@getPendingPrList');
    Route::get('/odash/prapproval/{id}','OperationController@getPrDetails');
    Route::put('/odash/prapproval/{id}','OperationController@addRemarks');
    Route::put('/odash/prapproval/action/{id}','OperationController@requestionAction');
    Route::get('/odash/customer', 'CustomerController@getCustomers');
    Route::post('/odash/customer/{id}', 'CustomerController@recommendCustomer');

  });

	//user management
	Route::group(['middleware' => ['checkPrivelege']], function() {

		Route::get('/users', 'UserController@getAllUser');
		Route::post('/users', 'UserController@createUser');
		Route::post('/users/{id}', 'UserController@editUser');
		Route::delete('/users/{id}','UserController@deleteUser');

	});

});

