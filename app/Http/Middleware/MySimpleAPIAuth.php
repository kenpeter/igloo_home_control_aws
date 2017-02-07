<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

// https://laravel.com/docs/5.2/authentication#stateless-http-basic-authentication
// http://laravel.io/forum/12-07-2015-how-to-create-custom-http-authentication-laravel-51
// https://github.com/WP-API/WP-API/issues/509
// https://www.getpostman.com/docs/helpers
class MySimpleAPIAuth
{
  public function handle($request, Closure $next)
  {
    // need to user postman to post username and password
    // e.g. username: rest_api_username
    // e.g. password: pideyRojwyt9
    // e.g. key: Authorization
    // e.g. value: Basic cmVzdF9hcGlfdXNlcm5hbWU6cGlkZXlSb2p3eXQ5
    if (!$this->basic_validate($request->header('PHP_AUTH_USER'), $request->header('PHP_AUTH_PW'))) {
      $data = ['error' => ['message' => 'API username and password not correct', 'status_code' => 403]];
      return response()->json($data, 404, array(), JSON_PRETTY_PRINT);
    }
    return $next($request);
  }

  private function basic_validate($user, $password)
  {
    if($user == 'rest_api_username' && $password == 'pideyRojwyt9') {
      return true;
    }

    return false;
  }

}
