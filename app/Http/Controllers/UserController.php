<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use JWTAuth;
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


		$user = User::where([ [$access,1] , ['username', $username] ])->first();
		// return $user;
    if ($user && \Hash::check($request->password, $user->password)){ // The passwords match...
    	$token = JWTAuth::fromUser($user);

			// Cookie::make('TOKEN',$token,auth('api')->factory()->getTTL() * 60);
    	return response()->json(['success' => true, 'message' => 'Login Success', 'access_token' => $token,'token_type' => 'Bearer',
    		'expires_in' => auth('api')->factory()->getTTL() * 60 ]);
    }

    return response()->json(['success' => false, 'message' => 'Invalid username or password'],401);

  }

  public function me()
  {
  	return response()->json(auth()->user());
  }

  public function logout()
  {
  	auth()->logout();

  	return response()->json(['message' => 'Successfully logged out']);
	}  //
}
