<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserLogs;
use JWTAuth;
use Storage;
use Validator;
use Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
class UserController extends Controller
{
	public function login(Request $request,$sys)
	{
		$username = $request->username;
		$password = $request->password;
    if($sys === 'cposms')
			$access = 'cposms_access';
    else if($sys === 'pjoms')
      $access = 'pjoms_access';
    else if($sys === 'pmms')
      $access = 'pmms_access';
    else if($sys === 'cims')
      $access = 'cims_access';
    else if($sys === 'wims')
      $access = 'wims_access';
    else if($sys === 'psms')
      $access = 'psms_access';
    else if($sys === 'salesms')
      $access = 'salesms_access';
    else if($sys === 'purchasesms')
      $access = 'purchasesms_access';
    else if($sys === 'approvalpr')
      $access = 'approval_pr';
    else {
      return response()->json(
        [
          'error' => ['Invalid access']
        ],422);
    }

    if($sys === 'pmms')
      $user = User::where('username', $username)->first();
    else
      $user = User::where([ [$access,1] , ['username', $username] ])->first();
		// return $user;
    if ($user && Hash::check($request->password, $user->password)){ // The passwords match...
    	$token = JWTAuth::fromUser($user);

     UserLogs::create(
      [
        'user_id' => $user->id,
        'action' => 'Logged in',
        'system' => $sys,
      ]);
     return response()->json(['message' => 'Login Success', 'access_token' => $token,'token_type' => 'Bearer',
      'expires_in' => auth('api')->factory()->getTTL() * 60 ]);
   }
   return response()->json(['message' => 'Invalid username or password'],401);

 }

 public function me()
 {
   return response()->json(auth()->user());
 }

 public function logout($sys)
 {
  UserLogs::create(
    [
      'user_id' => auth()->user()->id,
      'action' => 'Logged out',
      'system' => $sys,
    ]);
  auth()->logout();

  return response()->json(['message' => 'Successfully logged out']);
	}  //

  public function getAllUser()
  {

    $users = User::all();
    $users_arr = array();

    foreach($users as $user){
      array_push($users_arr,$this->getUser($user));
    }

    return response()->json(
      [
        'users' => $users_arr
      ]);

  }

  public function getUser($user){

    return array(
      'id' => $user->id,
      'username' => $user->username,
      'type' => $user->type,
      'npd' => $user->npd_access,
      'pmms' => $user->pmms_access,
      'cposms' => $user->cposms_access,
      'pjoms' => $user->pjoms_access,
      'cims' => $user->cims_access,
      'wims' => $user->wims_access,
      'psms' => $user->psms_access,
      'salesms' => $user->salesms_access,
      'prapproval' => $user->approval_pr,
      'poapproval' => $user->approval_po,
      'firstname' => $user->fullname,
      'middleinitial' => $user->middleinitial,
      'lastname' => $user->lastname,
      'extensionname' => $user->extensionname,
      'gender' => $user->gender,
      'email' => $user->email,
      'department' => $user->department,
      'position' => $user->position,
      'signature' => $user->signature,
    );

  }

  public function createUser(Request $request)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'username' => 'min:2|max:12|required|unique:users_account,username',
        'password' => 'min:3|max:12|required',
        'type' => 'min:4|max:20|required|in:default,admin,management',
        'department' => 'min:2|max:20|required',
        'gender' => 'string|max:6|in:male,female|required',
        'password' => 'min:3|max:12|required',
        'npd' => 'boolean|nullable',
        'pmms' => 'boolean|nullable',
        'cposms' => 'boolean|nullable',
        'pjoms' => 'boolean|nullable',
        'cims' => 'boolean|nullable',
        'wims' => 'boolean|nullable',
        'psms' => 'boolean|nullable',
        'salesms' => 'boolean|nullable',
        'prapproval' => 'boolean|nullable',
        'poapproval' => 'boolean|nullable',
        'firstname' => 'string|max:50|required',
        'middleinitial' => 'string|max:5|required',
        'lastname' => 'string|max:50|required',
        'extensionname' => 'string|max:5|nullable',
        'position' => 'string|max:50|required',
        'signature' => 'nullable|mimes:png,jpg,jpeg|max:2000',
      ]
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $user = new User();
    $user->fill([
      'username' => $request->username,
      'password' => Hash::make($request->password),
      'type' => strtolower($request->type),
      'gender' => strtolower($request->gender),
      'department' => strtolower($request->department),
      'npd_access' => $request->npd,
      'pmms_access' => $request->pmms,
      'cposms_access' => $request->cposms,
      'pjoms_access' => $request->pjoms,
      'cims_access' => $request->cims,
      'wims_access' => $request->wims,
      'psms_access' => $request->psms,
      'salesms_access' => $request->salesms,
      'approval_pr' => $request->prapproval,
      'approval_po' => $request->poapproval,
      'firstname' => ucwords($request->firstname),
      'middleinitial' => strtoupper($request->middleinitial),
      'lastname' => ucwords($request->lastname),
      'extensionname' => ucwords($request->extensionname),
      'position' => ucwords($request->position),
    ]);
    $user->save();

    if($request->signature){
      $name = pathinfo($request->signature->getClientOriginalName(),PATHINFO_FILENAME);
      $ext = $request->signature->getClientOriginalExtension();
      $filename =  "sig_".$user->id.".".$ext;
      Storage::disk('local')->putFileAs('/users/signature/'.$user->id.'/',$request->signature, $filename);
      $user->fill([
        'signature' => $filename,
      ]);
      $user->save();
    }

    $user->refresh();
    $newUser = $this->getUser($user);

    return response()->json(
      [
        'newUser' => $newUser,
        'message' => 'User created'
      ]); 

  }

  public function editUser(Request $request,$id)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'username' => 'min:2|max:12|required|unique:users_account,username,'.$id,
        'password' => 'min:3|max:12|required',
        'type' => 'min:4|max:20|required|in:default,admin,management',
        'department' => 'min:2|max:20|required',
        'gender' => 'string|max:6|in:male,female|required',
        'password' => 'min:3|max:12|required',
        'npd' => 'boolean|nullable',
        'pmms' => 'boolean|nullable',
        'cposms' => 'boolean|nullable',
        'pjoms' => 'boolean|nullable',
        'cims' => 'boolean|nullable',
        'wims' => 'boolean|nullable',
        'psms' => 'boolean|nullable',
        'salesms' => 'boolean|nullable',
        'prapproval' => 'boolean|nullable',
        'poapproval' => 'boolean|nullable',
        'firstname' => 'string|max:50|required',
        'middleinitial' => 'string|max:5|required',
        'lastname' => 'string|max:50|required',
        'extensionname' => 'string|max:5|nullable',
        'position' => 'string|max:50|required',
        'signature' => 'nullable|mimes:png,jpg,jpeg|max:2000',
      ]
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $user = User::findOrFail($id);
    $user->fill([
      'username' => $request->username,
      'type' => strtolower($request->type),
      'gender' => strtolower($request->gender),
      'department' => strtolower($request->department),
      'npd_access' => $request->npd,
      'pmms_access' => $request->pmms,
      'cposms_access' => $request->cposms,
      'pjoms_access' => $request->pjoms,
      'cims_access' => $request->cims,
      'wims_access' => $request->wims,
      'psms_access' => $request->psms,
      'salesms_access' => $request->salesms,
      'approval_pr' => $request->prapproval,
      'approval_po' => $request->poapproval,
      'firstname' => ucwords($request->firstname),
      'middleinitial' => strtoupper($request->middleinitial),
      'lastname' => ucwords($request->lastname),
      'extensionname' => ucwords($request->extensionname),
      'position' => ucwords($request->position),
    ]);

    if($request->signature){
      $name = pathinfo($request->signature->getClientOriginalName(),PATHINFO_FILENAME);
      $ext = $request->signature->getClientOriginalExtension();
      $filename =  "sig_".$user->id.".".$ext;
      Storage::disk('local')->putFileAs('/users/signature/'.$id.'/',$request->signature, $filename);
      $user->fill([
        'signature' => $filename,
      ]);
    }

    if(!Hash::check($request->password, $user->password)){
      $user->password = Hash::make($request->password);
    }

    $user->save();
    $user->refresh();
    $newUser = $this->getUser($user);
    return response()->json(
      [
        'newUser' => $newUser,
        'message' => 'User updated'
      ]); 

  }

  public function deleteUser($id)
  {
    $user = User::findOrFail($id)->delete();

    return response()->json(
      [
        'message' => 'User deleted'
      ]);

  }

}
