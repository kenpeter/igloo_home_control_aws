<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Closure;


// https://stackoverflow.com/questions/28402726/laravel-5-redirect-to-https
class GetDeviceInfoAuth
{
	/**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
  public function handle($request, Closure $next)
  {

    /*
    var_dump("test");
    die;

    if(env('APP_ENV') === 'prod') {
      
      
    }

		return $next($request);
    */

    return $this->myOnceBasic("username", $request) ?: $next($request);
  }

  protected function myOnceBasic($field, $request)
  {
    $credentials = $this->myGetBasicCredentials($field, $request);

    var_dump($credentials);
    die;

    /*
    if (! $this->once(array_merge($credentials, $extraConditions))) {
        return $this->getBasicResponse();
    }
    */

  }



  protected function myGetBasicCredentials($field, Request $request)
  {
    return [$field => $request->getUser(), 'password' => $request->getPassword()];
  }

}
