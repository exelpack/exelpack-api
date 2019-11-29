<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserLogs;
use JWTAuth;
use Validator;
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


		$user = User::where([ [$access,1] , ['username', $username] ])->first();
		// return $user;
    if ($user && \Hash::check($request->password, $user->password)){ // The passwords match...
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

  public function createUser(Request $request)
  {

    $validator = Validator::make([
      $request->all(),
      [
        'username' => 'min:2|max:12|required',
        'password' => 'min:3|max:12|required',
        'type' => 'min:4|max:20|required',
        'department' => 'min:2|max:20|required',
        'password' => 'min:3|max:12|required',
        'npd' => 'boolean|nullable',
        'pmms' => 'boolean|nullable',
        'cposms' => 'boolean|nullable',
        'pjoms' => 'boolean|nullable',
        'cims' => 'boolean|nullable',
      ]
    ]);

    if($validator->fails()){
      return response()->json(['errors' => $validator->errors()->all()],422);
    }

    $user = new User();
    $user->fill([
      'username' => $request->username,
      'password' => $request->password,
      'type' => $request->type,
      'department' => $request->department,
      'npd_access' => $request->npd,
      'pmms_access' => $request->pmms,
      'cposms_access' => $request->cposms,
      'pjoms_access' => $request->pjoms,
      'cims_access' => $request->cims,
    ]);
    $user->save();

  }

}
