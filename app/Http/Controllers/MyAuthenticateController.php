<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;

use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;

class MyAuthenticateController extends Controller
{
	public function __construct()
	{
		$this->middleware('jwt.auth', ['except' => ['authenticate']]);
	}

	public function index()
  {
    return 'hi';
  }

	/**
    * Return a JWT
    *
    * @return Response
    */
  public function authenticate(Request $request) {
    $credentials = $request->only('username', 'password');

    try {
			$issuedAt = time();
      $notBefore = $issuedAt + 10; // cannot use nbf, why? 
			$expire = $notBefore + 60*30; // token valid for half hour

			$claim = array(
				'iat' => $issuedAt,
				'exp' => $expire,
			);

      if (! $token = JWTAuth::attempt($credentials, $claim)) {
        return response()->json(['error' => 'invalid_credentials'], 401);
      }
    } 
		catch (JWTException $e) {
    	return response()->json(['error' => 'could_not_create_token'], 500);
    }

    return response()->json(compact('token'));
	}

}
