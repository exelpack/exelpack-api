<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use Validator;
use App\Customers;

class CustomerController extends Controller
{
  public function getCustomer($customer){
    return array(
      'id' => $customer->id,
      'bi_name' => $customer->companyname,
      'bi_address' => $customer->companyaddress,
      'bi_nature' => $customer->companynature,
      'bi_premises' => $customer->companypremises,
      'bi_type' => $customer->companybusinesstype,
      'bi_yearsBusiness' => $customer->companyoperationyears,
      'bi_contact' => $customer->companycontactperson,
      'bi_contactposition' => $customer->companycontactposition,
      'bi_tel' => $customer->companytelephone,
      'bi_fax' => $customer->companyfax,
      'bi_sss' => $customer->companysss,
      'bi_tin' => $customer->companytin,
      'bi_email' => $customer->companyemail,
      'bi_nationality' => $customer->ownernationality,
      'isApproved' => $customer->approval_status,
      'approvedBy' => $customer->approved_by,
      'comment' => $customer->comment
    );
  }

  public function fillCustomer($request){
    return array(
      'companyname' => strtoupper($request->bi_name),
      'companyaddress' => strtoupper($request->bi_address),
      'companynature' => $request->bi_nature,
      'companypremises' => $request->bi_premises,
      'companyoperationyears' => $request->bi_yearsBusiness,
      'companybusinesstype' => $request->bi_type,
      'companycontactperson' => ucwords($request->bi_contact),
      'companycontactposition' => ucwords($request->bi_contactposition),
      'companytelephone' => $request->bi_tel,
      'companyfax' => $request->bi_fax,
      'companysss' => $request->bi_sss,
      'companytin' => $request->bi_tin,
      'companyemail' => $request->bi_email,
      'ownernationality' => $request->bi_nationality,
    );
  }

  public function getCustomers()
  {
    $customers = Customers::all()->map(function($customer) {
      return $this->getCustomer($customer);
    });

    return response()->json([
      'customers' => $customers,
    ]);
  }

  public function addCustomer(Request $request){

    $validator = Validator::make($request->all(),
      array(
        'bi_name' => 'string|required|regex:/^[a-zA-Z0-9-_ ]*$/
          |unique:customer_information,companyname',
        'bi_address' => 'required|max:150',
        'bi_nature' => 'required|max:150',
        'bi_type' => 'required|max:50',
        'bi_premises' => 'required|max:10',
        'bi_contact' => 'required|max:50',
        'bi_contactposition' => 'required|max:50',
        'bi_yearsBusiness' => 'required|integer',
        'bi_fax' => 'nullable|regex:/^[0-9-]+$/',
        'bi_tel' => 'nullable|regex:/^[0-9-]+$/',
        'bi_nationality' => 'string|required'
      ), [],
      array(
        'bi_name' => 'company name',
        'bi_address' => 'address',
        'bi_nature' => 'nature',
        'bi_type' => 'type',
        'bi_contact' => 'contact',
        'bi_premises' => 'premises',
        'bi_yearsBusiness' => 'years in business',
        'bi_contactposition' => 'contact position',
        'bi_fax' => 'Fax',
        'bi_tel' => 'Telephone',
      )
    );
    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()]);

    $cinfo = new Customers;
    $cinfo->fill($this->fillCustomer($request));
    $cinfo->save();
    $cinfo->refresh();
    return response()->json([
      'newCustomer' => $this->getCustomer($cinfo),
      'message' => 'Record added',
    ]);
  }

  public function updateCustomer(Request $request, $id){
    $validator = Validator::make($request->all(),
      array(
        'bi_name' => 'string|required|regex:/^[a-zA-Z0-9-_ ]*$/
          |unique:customer_information,companyname,'.$id,
        'bi_address' => 'required|max:150',
        'bi_nature' => 'required|max:150',
        'bi_type' => 'required|max:50',
        'bi_premises' => 'required|max:10',
        'bi_contact' => 'required|max:50|regex:/^[a-zA-Z ]*$/',
        'bi_contactposition' => 'required|max:50',
        'bi_yearsBusiness' => 'required|integer',
        'bi_fax' => 'string|nullable|regex:/^[0-9-]+$/',
        'bi_tel' => 'string|nullable|regex:/^[0-9-]+$/',
        'bi_nationality' => 'string|required'
      ), [],
      array(
        'bi_name' => 'company name',
        'bi_address' => 'address',
        'bi_nature' => 'nature',
        'bi_type' => 'type',
        'bi_contact' => 'contact',
        'bi_premises' => 'premises',
        'bi_yearsBusiness' => 'years in business',
        'bi_contactposition' => 'contact position',
        'bi_fax' => 'Fax',
        'bi_tel' => 'Telephone',
      )
    );
    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()]);

    $cinfo = Customers::findOrFail($id);
    $cinfo->fill($this->fillCustomer($request));
    $cinfo->save();

    return response()->json([
      'newCustomer' => $this->getCustomer($cinfo),
      'message' => 'Record updated',
    ]);
  }

  public function deleteCustomer($id){
    $cinfo = Customers::findOrFail($id);
    $cinfo->delete();
   
    return response()->json([
      'message' => 'Record deleted',
    ]);
  }

  public function recommendCustomer(Request $request, $id) {

    $validator = Validator::make($request->all(), [
      'method' => 'string|in:approved,rejected,APPROVED,REJECTED|required',
    ]);
    if($validator->fails())
      return response()->json(['errors' => $validator->errors()->all()] ,422);

    $customer = Customers::findOrFail($id);

    $method = strtolower($request->method);
    $approvalStatus = 'PENDING APPROVAL';
    if($method === 'approved')
        $approvalStatus = 'APPROVED';
    else if($method === 'rejected')
        $approvalStatus = 'REJECTED';

    $username = auth()->user()->username;
    $customer->fill([
      'approval_status' => $approvalStatus,
      'recommended_by' => $username,
      'recommended_date' => date('Y-m-d'),
      'approved_by' => $username,
      'approval_date' => date('Y-m-d'),
    ]);
    $customer->save();

    return response()->json([
      'newCustomer' => $this->getCustomer($customer),
      'message' => 'Customer successfully marked as '.$approvalStatus,
    ]);
  }

}
