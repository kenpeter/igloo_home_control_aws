<?php

namespace App\Http\Middleware;

use Closure;


// https://stackoverflow.com/questions/28402726/laravel-5-redirect-to-https
class HttpsProtocol
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
    if(env('APP_ENV') === 'prod') {
      if(!$request->secure()) {
        // https://laravel.com/docs/5.1/requests
        $uri = $request->path();

        // http://toothfi.com/device/device/smt_770/60:01:94:00:56:34/get_device_info
        // the thermostat is able to parse non https json
				// 
				// http://toothfi.com/device/device/wifi_gateway/set_me_up/prod/get_firmware
				// allow firmware to be downloaded, no https
        if(
          (strstr($uri, "device/device") !== false &&
          strstr($uri, "get_device_info") !== false) ||

					(strstr($uri, "device/device") !== false &&
          strstr($uri, "get_firmware") !== false)	
        ) {
          // do nothing and allow http request go through
        }
        else {
          // otherwise, http to https
          return redirect()->secure($request->getRequestUri());
        }
      }
      else {

      }
    }
    else {

    }


		return $next($request);
  }
}
