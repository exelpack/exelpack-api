<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserLogs;
use JWTAuth;
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
    else {
      return response()->json(
        [
          'error' => ['Invalid access']
        ],422);
    }


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
     return response()->json(['success' => true, 'message' => 'Login Success', 'access_token' => $token,'token_type' => 'Bearer',
      'expires_in' => auth('api')->factory()->getTTL() * 60 ]);
   }

   return response()->json(['success' => false, 'message' => 'Invalid username or password'],401);

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
      'department' => $user->department,
      'npd' => $user->npd_access,
      'pmms' => $user->pmms_access,
      'cposms' => $user->cposms_access,
      'pjoms' => $user->pjoms_access,
      'cims' => $user->cims_access,
      'wims' => $user->wims_access,
      'psms' => $user->psms_access,
      'salesms' => $user->salesms_access,
      'fullname' => $user->fullname,
      'position' => $user->position,
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
        'password' => 'min:3|max:12|required',
        'npd' => 'boolean|nullable',
        'pmms' => 'boolean|nullable',
        'cposms' => 'boolean|nullable',
        'pjoms' => 'boolean|nullable',
        'cims' => 'boolean|nullable',
        'wims' => 'boolean|nullable',
        'psms' => 'boolean|nullable',
        'salesms' => 'boolean|nullable',
        'fullname' => 'string|max:50|required',
        'position' => 'string|max:50|required',
        'signature' => 'string|nullable',
      ]
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $user = new User();
    $user->fill([
      'username' => $request->username,
      'password' => Hash::make($request->password),
      'type' => $request->type,
      'department' => $request->department,
      'npd_access' => $request->npd,
      'pmms_access' => $request->pmms,
      'cposms_access' => $request->cposms,
      'pjoms_access' => $request->pjoms,
      'cims_access' => $request->cims,
      'wims_access' => $request->wims,
      'psms_access' => $request->psms,
      'salesms_access' => $request->salesms,
      'fullname' => $request->fullname,
      'position' => $request->position,
    ]);
    $user->save();
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
        'password' => 'min:3|max:12|required',
        'npd' => 'boolean|nullable',
        'pmms' => 'boolean|nullable',
        'cposms' => 'boolean|nullable',
        'pjoms' => 'boolean|nullable',
        'cims' => 'boolean|nullable',
        'wims' => 'boolean|nullable',
        'psms' => 'boolean|nullable',
        'salesms' => 'boolean|nullable',
        'fullname' => 'string|max:50|required',
        'position' => 'string|max:50|required',
        'signature' => 'string|nullable',
      ]
    );

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $user = User::findOrFail($id);
    $user->fill([
      'username' => $request->username,
      'type' => $request->type,
      'department' => $request->department,
      'npd_access' => $request->npd,
      'pmms_access' => $request->pmms,
      'cposms_access' => $request->cposms,
      'pjoms_access' => $request->pjoms,
      'cims_access' => $request->cims,
      'wims_access' => $request->wims,
      'psms_access' => $request->psms,
      'salesms_access' => $request->salesms,
      'fullname' => $request->fullname,
      'position' => $request->position,
    ]);

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
