<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use \Illuminate\Http\Request;
use \Illuminate\Http\Response;

use JWTAuth;
use Illuminate\Support\Facades\Password;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

// Hak
use App\MyTrait\TraitLib;


class PasswordController extends Controller
{
  /*
  |--------------------------------------------------------------------------
  | Password Reset Controller
  |--------------------------------------------------------------------------
  |
  | This controller is responsible for handling password reset requests
  | and uses a simple trait to include this behavior. You're free to
  | explore this trait and override any methods you wish to tweak.
  |
  */

  use ResetsPasswords;

  // Hak
  use TraitLib;

  /**
   * Create a new password controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest');
  }


  // http://laravel.io/forum/11-12-2015-how-to-fire-an-event-after-successfull-password-reset
  // Hak
  // overwrite
  /**
  * Reset the given user's password.
  *
  * @param  \Illuminate\Http\Request  $request
  * @return \Illuminate\Http\Response
  */
  public function reset(Request $request)
  {
    //
    $json = array();

    $this->validate(
      $request,
      $this->getResetValidationRules(),
      $this->getResetValidationMessages(),
      $this->getResetValidationCustomAttributes()
    );

    $credentials = $this->getResetCredentials($request);

    // reset password
    $broker = $this->getBroker();
    $response = Password::broker($broker)->reset($credentials, function ($user, $password) {
      $this->resetPassword($user, $password);
    });

    switch ($response) {
      case Password::PASSWORD_RESET:
        $email = $credentials["email"];
        $user_obj = $this->get_user_by_email($email, $json);

        // Invalidate token in different deivces, app, etc for this user.
        $this->invalid_user_token($user_obj->id, $json);        

        // Hak
        // Generate new mqtt password
        // and insert into table
        $this->gen_mqtt_password_and_update_user_mqtt_password($user_obj, $json);

        return $this->getResetSuccessResponse($response);
      default:
        return $this->getResetFailureResponse($request, $response);
    }
  }


  public function get_user_by_email($email, &$json) {
    $user_obj = \DB::table("users")
      ->where("email", $email)
      ->first(); 

    if($user_obj == NULL) {
      $json["error_msg"][] = "user_not_exist";    
    }
    else {
      $json["msg"][] = "user_exist";
    }

    return $user_obj;
  }


  

  // NOTE: exception needs to be exist in the top definition
  // https://laravel.io/forum/05-21-2015-try-catch-not-working
  public function invalid_user_token($user_id, &$json) {
    $objs = \DB::table("user_identity_token")
      ->where("user_id", $user_id)
      ->select('token')
      ->get();      
    
    foreach($objs as $obj) {
      $token = $obj->token;
      
      try {
        JWTAuth::invalidate($token);
      }
      catch(TokenBlacklistedException $e) {
        $json["error_msg"][] = "token_refresh_only_once: ". $token;
      }
      catch (TokenInvalidException $e) {
        $json["error_msg"][] = "token_invalid: ". $token;
      }
      catch (TokenExpiredException $e) {
        $json["error_msg"][] = "token_expired: ". $token;
      }
      catch (JWTException $e) {
        $json["error_msg"][] = "jwt_exception: ". $token;
      }
    }
    
  }

  
  public function gen_mqtt_password_and_update_user_mqtt_password(&$user_obj, &$json) {
    $mqtt_username = $user_obj->username;
    $mqtt_password = $this->gen_strong_pass();    
    $hash_mqtt_password = hash("sha256", $mqtt_password);    

    // iv mqtt username
    $iv_mqtt_username = $this->gen_iv();
    $encrypt_mqtt_username = $this->my_openssl_encrypt($mqtt_username, $iv_mqtt_username);

    // iv mqtt password
    $iv_mqtt_password = $this->gen_iv();
    $encrypt_mqtt_password = $this->my_openssl_encrypt($mqtt_password, $iv_mqtt_password);

    // insert into user_mqtt_password table
    $this->update_user_mqtt_password(
      $mqtt_username, 
      $encrypt_mqtt_username, 
      $encrypt_mqtt_password,  
      $hash_mqtt_password,
 
      $iv_mqtt_username, 
      $iv_mqtt_password, 
      $json
    );

  }

}
