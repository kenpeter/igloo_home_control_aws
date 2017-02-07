<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;

use \Illuminate\Http\Request;
use \Illuminate\Http\Response;
use JWTAuth;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;


class AuthController extends Controller
{
  /*
  |--------------------------------------------------------------------------
  | Registration & Login Controller
  |--------------------------------------------------------------------------
  |
  | This controller handles the registration of new users, as well as the
  | authentication of existing users. By default, this controller uses
  | a simple trait to add these behaviors. Why don't you explore it?
  |
  */

  use AuthenticatesAndRegistersUsers, ThrottlesLogins;

  /**
   * Where to redirect users after login / registration.
   *
   * @var string
   */

  // Hak
  protected $redirectTo = '/home';

  // Hak
  // http://laraveldaily.com/auth-login-with-username-instead-of-email/
  protected $username = 'username';

  // Hak
  // https://stackoverflow.com/questions/29797433/how-to-change-the-redirect-url-when-logging-out
  protected $redirectAfterLogout = '/login';


  // Hak
  private $token_ttl = 60;
  // http://stackoverflow.com/questions/11821195/use-of-initialization-vector-in-openssl-encrypt  
  private $secret_hash = "HAPVOiUTW!@y&W7#Z2P6XKNvXIu6Qaxt"; // 32 chars
  private $encryption_method = "AES-256-CBC";

  /**
   * Create a new authentication controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware($this->guestMiddleware(), ['except' => 'logout']);
  }


  // Hak
  // http://www.easylaravelbook.com/blog/2015/09/25/adding-custom-fields-to-a-laravel-5-registration-form/
  // http://stackoverflow.com/questions/28417977/custom-validator-in-laravel-5
  /**
   * Get a validator for an incoming registration request.
   *
   * @param  array  $data
   * @return \Illuminate\Contracts\Validation\Validator
   */
  protected function validator(array $data)
  {
    return Validator::make($data, [
      'name' => 'required|max:255',
      'username' => 'required|unique:users|my_unique_username',
      'email' => 'required|email|max:255|unique:users',
      'password' => 'required|min:6|confirmed',
    ]);
  }


  /**
   * Create a new user instance after a valid registration.
   *
   * @param  array  $data
   * @return User
   */
  protected function create(array $data)
  {
    return User::create([
      'name' => $data['name'],
      'username' => $data['username'],
      'email' => $data['email'],
      'password' => bcrypt($data['password']),
    ]);
  }


  // Overwrite
  /**
   * Handle a registration request for the application.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function register(Request $request)
  {
    $validator = $this->validator($request->all());

    if ($validator->fails()) {
      $this->throwValidationException(
          $request, $validator
      );
    }

    \Illuminate\Support\Facades\Auth::guard($this->getGuard())->login($this->create($request->all()));
    
    // Hak
    // code above actually creates a new users.
    // insert into user_identity_token
    // user needs to exist first.
    $this->create_token($request);

    return redirect($this->redirectPath());
  }

  
  // New user is just created.
  private function create_token(Request $request) {
    $username = $request->input("username");
    $email = $request->input("email");
    // This password is diff from mqtt password.
    // We give user a mqtt password.
    $password = $request->input("password"); 

    // 
    $json = array();

    $credentials = array(
      "username" => $username,
      "password" => $password
    );

    try {
      $issuer = "backend_server";
      $subject = "user_auth";
      $audience = "app";
			$issue_at = time();
      $not_before = $issue_at; // cannot use nbf, why? 

      // You can set a very short time here,
      // but when it refreshes, it uses the jwt default setting.
      // so need to set to the same setting as default.
      $expire = $not_before + $this->token_ttl;

      // 
      $mqtt_username = $username;
      $this->create_encrypt_mqtt_username_password_msg(
        $mqtt_username,
        $encrypt_mqtt_username,
        $encrypt_mqtt_password,
        $encrypt_mqtt_username_msg, 
        $encrypt_mqtt_password_msg,
        
        $iv_mqtt_username,
        $iv_mqtt_password,
        $json
      );

      // https://scotch.io/tutorials/the-anatomy-of-a-json-web-token
			$claim = array(
        "iss" => $issuer,
        "sub" => $subject,
        "aud" => $audience,
				'iat' => $issue_at,
				'exp' => $expire,

        'mqtt_username' => base64_encode($encrypt_mqtt_username_msg),
        'mqtt_password' => base64_encode($encrypt_mqtt_password_msg),
			);

      if (! $token = JWTAuth::attempt($credentials, $claim)) {
        die("token issue");
      }
    } 
		catch (JWTException $e) {
    	die("jwt exception");
    }

    // insert token into table
    // or overwrite the existing token
    $identity = "backend_server";
    $this->record_token($username, $identity, $token, $json);

    // insert into user_mqtt_password
    $this->insert_into_user_mqtt_password(
      $mqtt_username, 
      $encrypt_mqtt_username, 
      $encrypt_mqtt_password, 
      $password, 

      $iv_mqtt_username,
      $iv_mqtt_password, 
      $json
    );

  }


  private function record_token($username, $identity, $token, &$json) {
    $user = \DB::table('users')     
      ->where('username', $username)
      ->first();  
    
    if($user == NULL) {
      $json["msg"][] = "user_not_in_db";
    }
    else {
      $json["error_msg"][] = "user_in_db";
    }

    if($this->is_username_identity_token_exist($user->id, $identity, $json)) {
      // we don't record, but we update
      $this->the_update_token($user->id, $identity, $token, $json);
    }
    else {
      $this->the_record_token($user->id, $identity, $token, $json);
    }

  }  


  private function is_username_identity_token_exist($user_id, $identity, &$json) {
    $user_id_token_obj = \DB::table('user_identity_token')     
      ->where('user_id', $user_id)
      ->where('identity', $identity)
      ->first();

    if($user_id_token_obj == NULL) {
      $json["msg"][] = "user_identity_token_not_exist";
      return false;
    }
    else {
      $json["msg"][] = "user_identity_token_exist";
      return true;
    }
  }


  private function the_update_token($user_id, $identity, $token, &$json) {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "token" => $token,
      "updated_at" => date($format),    
    );
    $user = \DB::table('user_identity_token')
      ->where("user_id", $user_id)
      ->where("identity", $identity)    
      ->update($arr);

    $json["msg"][] = "token_updated";
  }


  private function the_record_token($user_id, $identity, $token, &$json) {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "user_id" => $user_id,
      "identity" => $identity,
      "token" => $token,
      "created_at" => date($format),
      "updated_at" => date($format),    
    );
    $user = \DB::table('user_identity_token')     
      ->insert($arr);

    $json["msg"][] = "token_record_created";
  }


  private function create_encrypt_mqtt_username_password_msg(
    $mqtt_username,
    &$encrypt_mqtt_username,
    &$encrypt_mqtt_password,
    &$encrypt_mqtt_username_msg, 
    &$encrypt_mqtt_password_msg, 

    &$iv_mqtt_username,
    &$iv_mqtt_password,
    &$json
  ) 
  {
    $obj = \DB::table("user_mqtt_password")
      ->where('username', $mqtt_username)
      ->first();
    
    if($obj == NULL) {
      $json["msg"][] = "username_not_in_user_mqtt_password";
    }
    else {
      $json["error_msg"][] = "username_already_in_user_mqtt_password";
      return;
    }

    // We give user a mqtt password
    $mqtt_password = $this->gen_strong_pass();

    // iv username
    $iv_mqtt_username = $this->gen_iv();
    $encrypt_mqtt_username = $this->my_openssl_encrypt($mqtt_username, $iv_mqtt_username);
    $encrypt_mqtt_username_msg = $iv_mqtt_username. $encrypt_mqtt_username;  

    // iv password
    $iv_mqtt_password = $this->gen_iv();
    $encrypt_mqtt_password = $this->my_openssl_encrypt($mqtt_password, $iv_mqtt_password);
    $encrypt_mqtt_password_msg = $iv_mqtt_password. $encrypt_mqtt_password;
  }


  // https://gist.github.com/tylerhall/521810
  // Generates a strong password of N length containing at least one lower case letter,
  // one uppercase letter, one digit, and one special character. The remaining characters
  // in the password are chosen at random from those four sets.
  //
  // The available characters in each set are user friendly - there are no ambiguous
  // characters such as i, l, 1, o, 0, etc. This, coupled with the $add_dashes option,
  // makes it much easier for users to manually type or speak their passwords.
  //
  // Note: the $add_dashes option will increase the length of the password by
  // floor(sqrt(N)) characters.
  private function gen_strong_pass($length = 9, $add_dashes = false, $available_sets = 'luds')
  {
	  $sets = array();
	  if(strpos($available_sets, 'l') !== false)
		  $sets[] = 'abcdefghjkmnpqrstuvwxyz';
	  if(strpos($available_sets, 'u') !== false)
		  $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	  if(strpos($available_sets, 'd') !== false)
		  $sets[] = '23456789';
	  if(strpos($available_sets, 's') !== false)
		  $sets[] = '!@#$%&*?';

	  $all = '';
	  $password = '';
	  foreach($sets as $set)
	  {
		  $password .= $set[array_rand(str_split($set))];
		  $all .= $set;
	  }

	  $all = str_split($all);
	  for($i = 0; $i < $length - count($sets); $i++)
		  $password .= $all[array_rand($all)];

	  $password = str_shuffle($password);

	  if(!$add_dashes)
		  return $password;

	  $dash_len = floor(sqrt($length));
	  $dash_str = '';
	  while(strlen($password) > $dash_len)
	  {
		  $dash_str .= substr($password, 0, $dash_len) . '-';
		  $password = substr($password, $dash_len);
	  }
	  $dash_str .= $password;
	  return $dash_str;
  }


  // http://stackoverflow.com/questions/11821195/use-of-initialization-vector-in-openssl-encrypt
  private function my_openssl_encrypt($msg, $iv) {
    $encrypt = openssl_encrypt($msg, $this->encryption_method, $this->secret_hash, 0, $iv);
    return $encrypt;
  }


  private function insert_into_user_mqtt_password(
    $mqtt_username, 
    $encrypt_mqtt_username, 
    $encrypt_mqtt_password, 
    $password,

    $iv_mqtt_username, 
    $iv_mqtt_password, 
    &$json
  ) 
  {
    $hash_password = hash("sha256", $password);

    $format = "Y-m-d H:i:s";

    $arr = array(
      "username" => $mqtt_username,
      "encrypt_username" => $encrypt_mqtt_username,
      "other_encrypt_password" => $encrypt_mqtt_password,
      "password" => $hash_password,
      "username_iv" => $iv_mqtt_username,
      "other_encrypt_password_iv" => $iv_mqtt_password,
      "created_at" => date($format),
      "updated_at" => date($format),
    );
    \DB::table("user_mqtt_password")
      ->insert($arr);

    $json["msg"][] = "insert_into_user_mqtt_password_done";
  }


  private function gen_iv() {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    return $iv;
  }


}
