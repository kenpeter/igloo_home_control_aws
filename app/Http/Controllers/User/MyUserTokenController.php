<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;

use Tymon\JWTAuth\Validators\TokenValidator;
use Tymon\JWTAuth\Validators\PayloadValidator;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class MyUserTokenController extends Controller
{
  // https://laracasts.com/discuss/channels/general-discussion/laravel-52-re-using-password-reset-functionality
  use \Illuminate\Foundation\Auth\ResetsPasswords;

  // Hak
  use \App\MyTrait\TraitLib;

	// Hak, avoid collision to TraitLib
  //private $token_ttl = 60*60; // in seconds
  //private $token_ttl = 60;
  // http://stackoverflow.com/questions/11821195/use-of-initialization-vector-in-openssl-encrypt  
  //private $secret_hash = "HAPVOiUTW!@y&W7#Z2P6XKNvXIu6Qaxt"; // 32 chars
  //private $encryption_method = "AES-256-CBC";

  public function __construct()
	{
    /*
    {
      "error": "token_expired"
    }

    {
      "error": "token_invalid"
    }

    {
      "error": "token_not_provided"
    }  
    */
    // exception
		$this->middleware('jwt.auth', ['except' => [
      'user_account_register',
      'reset_password',
      'add_device_to_user',
      'remove_device_from_user',

      'get_token',
      'is_token_valid',
      'refresh_token',
      'invalidate_token', 

      'get_user_info',
    ]]);
	}


  // Check
  // NOTE: password needs to >= 8 chars long in laravel 5
  // If the phone registers a new user. It calls this, so 
  // new record will be created in user_identity_token
  // The phone will login straight away, then the phone will call get_token
  // another record will be created in user_identity_token.
  public function user_account_register(Request $request) {
    $format = "Y-m-d H:i:s";

    $name = trim($request->input("name"));
    $email = trim($request->input("email"));
    $username = trim($request->input("username"));
    $password = $request->input("password");

    // init json
    $json = array();

    // guard username in user and device table    
    if($this->is_username_already_exist($username)) {
      $json["error_msg"][] = "username_already_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;    
    }
    else {

    }


    // is username valid
    if(!$this->is_username_valid($username)) {
      $json["error_msg"][] = "username_not_in_valid_format_check_regular_expression";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }


    // guard email
    if($this->is_email_already_exist($email)) {
      $json["error_msg"][] = "email_already_exist";
      $json["success"] = false;  
      echo json_encode($json);
      return;
    }
    else {

    }

    // guard password
    if(strlen($password) < 8) {
      $json["error_msg"][] = "password_less_than_8_chars";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {

    }

    $hash_password = hash("sha256", $password); 
    $arr = array(
      "name" => $name,
      "email" => $email,
      "username" => $username,
      "password" => $hash_password,
      "created_at" => date($format),
      "updated_at" => date($format),
    );
  
    // http://smileyhappycoder.co.uk/server-side/fetching-last-insert-id-laravel/
    $user_id = \DB::table('users')
      ->insertGetId($arr);

    // mqtt username and password
    $mqtt_username = $username;
    $mqtt_password = $this->gen_strong_pass();    

    // iv mqtt username
    $iv_mqtt_username = $this->gen_iv();
    $encrypt_mqtt_username = $this->my_openssl_encrypt($mqtt_username, $iv_mqtt_username);
    $encrypt_msg_mqtt_username = $iv_mqtt_username. $encrypt_mqtt_username;

    // iv mqtt password
    $iv_mqtt_password = $this->gen_iv();
    $encrypt_mqtt_password = $this->my_openssl_encrypt($mqtt_password, $iv_mqtt_password);
    $encrypt_msg_mqtt_password = $iv_mqtt_password. $encrypt_mqtt_password;
    

    // table: user_identity_token
    // username, actual user
    // password, actual user password
    $identity = "backend_server";
    $info = array(
      "manufacturer" => "backend_server",
      "device_type" => "backend_server",
      "os_type" => "backend_server",
      "os_version" => "backend_server",
    );
    $info = json_encode($info);

    $this->local_get_token(
      $identity,
      $info,
      $user_id, 
      $username, 
      $password, 

      $encrypt_msg_mqtt_username, 
      $encrypt_msg_mqtt_password, 
      $json
    );

    // table: user_mqtt_password
    // username, mqtt_username
    // password, mqtt_password
    $this->insert_into_user_mqtt_password(
      $mqtt_username, 
      $encrypt_mqtt_username, 
      $encrypt_mqtt_password, 
      $mqtt_password, 
 
      $iv_mqtt_username, 
      $iv_mqtt_password, 
      $json
    );

    // msg
    $json["msg"][] = "user_is_created";
    $json["user_id"] = $user_id;
    $json["success"] = true;
  

    // email user info
    $mail_user_info = array(
      "from_mail" => "account@igloosoftware.com.au",
      "from_name" => "iGloo Account",
      "to_mail" => $email,      
      "username" => $username,
      "user_defined_name" => $name,
      "subject" => "Welcome to iGloo Home Energy Control",
    );
    $this->mail_user_register_info($mail_user_info);


    // output
    echo json_encode($json);
  }


  // Check
  // The actual reset happening when the user open his email
  // and click the reset link.
  public function reset_password(Request $request) {
    $email = $request->input("email");

    if(!empty($email)) {
      $request->merge(['email' => $email]);

      // Reset email sent
      $this->sendResetLinkEmail($request);

      //
      $json = array();
      $json["msg"][] = "reset_password_email_sent";
      $json["success"] = true;
      echo json_encode($json);
      return;
    }
    else {
      //
      $json = array();
      $json["error_msg"][] = "email_empty";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
  }


  // Check
  // Get token
  public function get_token(Request $request) {
    // user input
    $identity = $request->input("identity"); // device identity
    $username = $request->input("username");
    $password = $request->input("password");
    $info = $request->input("info"); // device info

    // json output
    $json = array();

    // expect device uuid
    if(empty($identity)) {
      $json["error_msg"][] = "identity_empty";
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "identity_not_empty";
    }

    // check username
    if(empty($username)) {
      $json["error_msg"][] = "username_empty";
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "username_not_empty";
    }

    // check password
    if(empty($password)) {
      $json["error_msg"][] = "password";
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "password_not_empty";
    }

    // devce info in json format
    // e.g. {"manufacturer":"SAMSUNG","device_type":"galaxy_s5","os_type":"Android","os_version":23}
    if(empty($info)) {
      $json["error_msg"][] = "info_empty";
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "info_not_empty";
    }

    // is user already exist
    if($this->is_human_user_already_exist($username, $json)) {
      // good
    }
    else {
      // bad
      echo json_encode($json);
      return;
    }
  

    // credential to gen token
    $credentials = array(
      "username" => $username,
      "password" => $password,
    );

    // building token
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

      // use mqtt_username to grab mqtt_username_msg and mqtt_password_msg
      $this->get_encrypt_mqtt_username_password_msg(
        $mqtt_username, 
        $encrypt_mqtt_username_msg, 
        $encrypt_mqtt_password_msg
      );
      
      // Now build the token with mqtt username and password
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

      // Actual build token
      if (! $token = JWTAuth::attempt($credentials, $claim)) {
        $json["error_msg"][] = "invalid_credentials";
        return response()->json($json, 200);
      }
    } 
		catch (JWTException $e) {
      $json["error_msg"][] = "could_not_create_token";
    	return response()->json($json, 200);
    }

    // Get user obj    
    $user = $this->get_user_by_username($username ,$json);

    // Invalidate the existing token for that identity and username
    $this->local_invalidate_token_by_user_id_and_identity($user->id, $identity, $json);
    
    // insert or overwrite the existing token
    $this->record_token($user->id, $identity, $info, $token, $json);

    // msg
    $json["user_id"] = $user->id;
    $json["token"] = $token;
    $json["success"] = true;

    return response()->json($json, 200);
	}


  // Check
  // refresh token
  // https://laracasts.com/discuss/channels/general-discussion/how-to-refreshing-jwt-token
  public function refresh_token(Request $request) {
    // user input
    $username = $request->input("username");
    $identity = $request->input("identity");    

    // output
    $json = array();

    // check username
    if(empty($username)) {
      $json["error_msg"][] = "username_empty";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "username_not_empty";
    }

    // check app, device identity
    if(empty($identity)) {
      $json["error_msg"][] = "identity_empty";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "identity_not_empty";
    }

    // In http header
    // Authorization: Bearer xxx.yyy.zzz
    // The token contains mqtt_username and mqtt_password
    $current_token = JWTAuth::getToken();

    // current token user has
    if(empty($current_token)) {
      $json["error_msg"][] = "current_token_empty";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "current_token_not_empty";
    }

    try{
      // After you refresh the token once, the old token cannot be used to refresh.
      // refresh also blacklists the token.    
      $token = JWTAuth::refresh($current_token);
    }
    catch(TokenBlacklistedException $e) {
      $json["error_msg"][] = "token_blacklisted";
      $json["success"] = false;
      return response()->json($json, 200);
    }
    catch (TokenInvalidException $e) {
      $json["error_msg"][] = "token_invalid";
      $json["success"] = false;
    	return response()->json($json, 200);
    }
    catch (TokenExpiredException $e) {
      $json["error_msg"][] = "token_expired";
      $json["success"] = false;
    	return response()->json($json, 200);
    }
    catch (JWTException $e) {
      $json["error_msg"][] = "jwt_exception";
      $json["success"] = false;
    	return response()->json($json, 200);
    }

    //
    $json["msg"][] = "current_token_refresh";

    // overwrite or create entry in user_identitty_token
    $this->update_token($username, $identity, $token, $json);

    //
    $json["token"] = $token;
    $json["success"] = true;

    return response()->json($json, 200);
  }

  
  // Check
  public function is_token_valid() {
    $json = array();
    $condi = false;
    $is_token_well_formed = false;
    $is_payload_valid = false;

    $token = JWTAuth::getToken();
    if($token == false) {
      $json["error_msg"][] = "token_empty";
      $json["success"] = false;
      return response()->json($json, 200);
    }

    // It doesn't tell you whether you can refresh or not.
    try{
      // https://github.com/tymondesigns/jwt-auth/issues/297
      $payload = JWTAuth::getPayload($token);

      $token_validator = new TokenValidator();
      $payload_validator = new PayloadValidator();
      
      // by looking at the soruce code
      $is_token_well_formed = $token_validator->isValid($token);
      $is_payload_valid = $payload_validator->isValid($payload->toArray());

      if($is_token_well_formed) {
        if($is_payload_valid) {
          $json["msg"][] = "token_well_formed";
          $json["msg"][] = "payload_valid";
        }
        else {
          $json["error_msg"][] = "payload_not_valid";
          $json["success"] = false;
          return response()->json($json, 200);
        }
      }
      else {
        $json["error_msg"][] = "token_not_well_formed";
        $json["success"] = false;
        return response()->json($json, 200);
      }
      
    }
    catch(TokenBlacklistedException $e) {
      $json["error_msg"][] = "token_blacklisted";
      $json["success"] = false;
      return response()->json($json, 200);
    }
    catch (TokenInvalidException $e) {
      $json["error_msg"][] = "token_invalid";
      $json["success"] = false;
    	return response()->json($json, 200);
    }
    catch (TokenExpiredException $e) {
      $json["error_msg"][] = "token_expired";
      $json["success"] = false;
    	return response()->json($json, 200);
    }
    catch (JWTException $e) {
      $json["error_msg"][] = "jwt_exception";
      $json["success"] = false;
    	return response()->json($json, 200);
    }

    //
    $json["success"] = true;
    return response()->json($json, 200);
  }


  // add device to user
  // token in http header
  public function add_device_to_user(Request $request) {
    // device_type == smt_770
    // $device_username == IGTST_108
    // $mqtt_user_type == human
    // $owner_username == test_android

    // user input
    $device_type = $request->input("device_type");
    $device_username = $request->input("device_username");
    $mqtt_user_type = $request->input("mqtt_user_type");
    $owner_username = $request->input("owner_username");

    // json output
    $json = array();

    // Validate token from header
    if(!$this->is_my_token_valid($json)) {
      // out
      return response()->json($json, 200);          
    } 

    // device type there?
    $device_type_obj = \DB::table('device_type')->where('name', $device_type)->first();
    if($device_type_obj == NULL) {
      $json["error_msg"][] = "device_type_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $device_type_name = $device_type_obj->name;
      $json["msg"][] = "device_type_exist";
    }


    // device there?
    $device_obj = \DB::table('device')->where('username', $device_username)->first();
    if($device_obj == NULL) {
      $json["error_msg"][] = "device_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $device_id = $device_obj->id;
      $json["msg"][] = "device_exist";
    }
    

    // mqtt user type there?
    $mqtt_user_type_obj = \DB::table('mqtt_user_type')->where('name', $mqtt_user_type)->first();
    if($mqtt_user_type_obj == NULL) {
      $json["error_msg"][] = "mqtt_user_type_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $mqtt_user_type_id = $mqtt_user_type_obj->id;
      $json["msg"][] = "mqtt_user_type_exist";
    }

    // owner username there?
    $owner_user = \DB::table('users')->where('username', $owner_username)->first();
    if($owner_user == NULL) {
      $json["error_msg"][] = "owner_username_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $owner_user_id = $owner_user->id;
      $json["msg"][] = "owner_user_exist";
    }

    // Only handle smt_770
    if($device_type_name == "smt_770") {
      // -------- link device to user in table ------------
      $this->connect_device_to_user($device_id, $owner_user_id, $json);


      // ------ link user and device in mqtt acl -------
      $this->connect_device_to_user_mqtt_acl($owner_user_id, $mqtt_user_type_id, $owner_username, $device_type_name, $device_username, $json);
      
    
      // ------- based on message, determine success or error ------
      $this->add_device_to_user_success_based_on_msg($json);

      echo json_encode($json);
      return;
    }
    else {

      //
      $json["error_msg"][] = "device_type_not_supported";
      $json["success"] = false;

      echo json_encode($json);
      return;
    }

  }


  // NOTE: not use in first stage, because we cannot remove device.
  // put token to header
  public function remove_device_from_user(Request $request) {
    // device_type == smt_770
    // $device_username == IGTST_108
    // $mqtt_user_type == human
    // $owner_username == test_android

    $device_type = $request->input("device_type");
    $device_username = $request->input("device_username");
    $mqtt_user_type = $request->input("mqtt_user_type");
    $owner_username = $request->input("owner_username");

    // json
    $json = array();

    // Validate token
    if(!$this->is_my_token_valid($json)) {
      // out
      return response()->json($json, 200);          
    }

    // device type
    $device_type_obj = \DB::table('device_type')->where('name', $device_type)->first();
    if($device_type_obj == NULL) {
      $json["error_msg"][] = "device_type_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $device_type_name = $device_type_obj->name;
      $json["msg"][] = "device_type_exist";
    }

    // device
    $device_obj = \DB::table('device')->where('username', $device_username)->first();
    if($device_obj == NULL) {
      $json["error_msg"][] = "device_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $device_id = $device_obj->id;
      $json["msg"][] = "device_exist";
    }
    
    // mqtt user type
    $mqtt_user_type_obj = \DB::table('mqtt_user_type')->where('name', $mqtt_user_type)->first();
    if($mqtt_user_type_obj == NULL) {
      $json["error_msg"][] = "mqtt_user_type_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $mqtt_user_type_id = $mqtt_user_type_obj->id;
      $json["msg"][] = "mqtt_user_type_exist";
    }

    // owner username
    $owner_user = \DB::table('users')->where('username', $owner_username)->first();
    if($owner_user == NULL) {
      $json["error_msg"][] = "owner_username_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $owner_user_id = $owner_user->id;
      $json["msg"][] = "owner_user_exist";
    }

    if($device_type_name == "smt_770") {
      // ---------- remove connection between device and user -----
      $this->remove_connection_device_and_user($device_id, $owner_user_id, $json); 

      // ---------- remove connection between device and user in mqtt acl table -----
      $this->remove_connection_device_and_user_mqtt_acl($owner_user_id, $mqtt_user_type_id, $owner_username, $device_type_name, $device_username, $json);

      // 
      $json["success"] = true;

      echo json_encode($json);
      return;
    }
    else {
      // 
      $json["msg"][] = "device_type_not_supported";
      $json["success"] = false;

      echo json_encode($json);
      return;
    }
  }


  // call this when mobile app logout of user
  public function invalidate_token() {
    $json = array();
    $token = JWTAuth::getToken();
    
    // token empty
    if($token == false) {
      $json["error_msg"][] = "token_empty";
      $json["success"] = false;
      return response()->json($json, 200);
    }

    // NOTE: we don't do anything to the token in the database.

    try {
      JWTAuth::invalidate($token);
    }
    catch(TokenBlacklistedException $e) {
      $json["error_msg"][] = "token_already_blacklist";
      $json["success"] = false;
      return response()->json($json, 200);
    }
    catch (TokenInvalidException $e) {
      $json["error_msg"][] = "token_invalid";
      $json["success"] = false;
      return response()->json($json, 200);
    }
    catch (TokenExpiredException $e) {
      $json["error_msg"][] = "token_expired";
      $json["success"] = false;
      return response()->json($json, 200);
    }
    catch (JWTException $e) {
      $json["error_msg"][] = "jwt_exception";
      $json["success"] = false;
      return response()->json($json, 200);
    }

    //
    $json["msg"][] = "invalidate_token_done";
    $json["success"] = true;
    return response()->json($json, 200);
  }


  public function get_user_info(Request $request) {
    // output
    $json = array();

    // Validate token
    if(!$this->is_my_token_valid($json)) {
      // out
      return response()->json($json, 200);          
    }

    // get token and parse
    $token = JWTAuth::getToken();
    $payload = JWTAuth::getPayload($token);

    // get custom claim or get custom data in token
    // https://github.com/tymondesigns/jwt-auth/issues/297
    $encrypt_msg_mqtt_username = $payload->get("mqtt_username");

    $mqtt_username = $this->trait_lib_decrypt_msg_get_mqtt_username($encrypt_msg_mqtt_username, $json);
    $username = $mqtt_username;

    // get user
    $user_obj = \DB::table('users')
      ->where('users.username', $username)
      ->select(
        'users.id',
        'users.name',
        'users.email',
        'users.created_at',
        'users.updated_at',
        'users.username'
      )
      ->first();

    if($user_obj == NULL) {
      $json["error_msg"][] = "username_not_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;
    }
    else {
      $json["msg"][] = "user_exist";
    }
    
    // get device username
    $device_obj = \DB::table('user_device')
      ->join('device', 'user_device.device_id', '=', 'device.id')
      ->where('user_device.user_id', $user_obj->id)
      ->select('device.username')
      ->get();

    //
    $json["id"] = $user_obj->id;
    $json["name"] = $user_obj->name;  
    $json["email"] = $user_obj->email;
    $json["created_at"] = $user_obj->created_at;
    $json["updated_at"] = $user_obj->updated_at;
    $json["username"] = $user_obj->username;

    foreach($device_obj as $single_device) {
      $json["has_device"][] = $single_device->username;
    }

    $json["success"] = true;
    echo json_encode($json);
  }


  private function update_token($username, $identity, $token, &$json) {
    $user = \DB::table('users')     
      ->where('username', $username)
      ->first();  
    
    if($user == NULL) {
      $json["error_msg"][] = "user_not_in_db";
    }
    else {
      $json["msg"][] = "user_in_db";

      if($this->is_username_identity_token_exist($user->id, $identity, $json)) {
        //
        $json["msg"][] = "username_identity_token_exist";

        // update token
        $this->the_update_token($user->id, $identity, $token, $json);        
      }
      else {
        //
        $json["msg"][] = "username_identity_token_not_exist";

        // insert
        $this->the_record_token($user->id, $identity, $token, $json);
        
      }
    }      

  }


  private function remove_connection_device_and_user($device_id, $owner_user_id, &$json) {
    if($this->does_device_connect_to_user($device_id, $owner_user_id)) {
      $this->the_remove_connection_device_and_user($device_id, $owner_user_id);
      $json["msg"][] = "connection_device_and_user_remove";
     }
    else { 
      $json["msg"][] = "device_user_has_no_connection";
    }    
  }


  private function the_remove_connection_device_and_user($device_id, $owner_user_id) {
    \DB::table('user_device')
      ->where('user_id', $owner_user_id)
      ->where('device_id', $device_id)  
      ->delete();
  }


  private function remove_connection_device_and_user_mqtt_acl($owner_user_id, $mqtt_user_type_id, $owner_username, $device_type_name, $device_username, &$json) {

    $topic = "device/". $device_type_name. "/". $device_username. "/#";  

    if($this->does_device_to_user_mqtt_acl_exist($owner_user_id, $topic, $mqtt_user_type_id, $owner_username)) {
      $this->the_remove_connection_device_and_user_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username);
      $json["msg"][] = "connection_device_and_user_mqtt_acl_remove";
    }
    else { 
      $json["msg"][] = "device_user_has_no_mqtt_acl_rule";
    }

  }


  private function the_remove_connection_device_and_user_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username) {
    \DB::table('mqtt_acl')
      ->where('user_id', $owner_user_id)
      ->where('topic', $topic)
      ->where('mqtt_user_type_id', $mqtt_user_type_id)
      ->where('username', $owner_username)
      ->delete();
  }


  private function connect_device_to_user_mqtt_acl($owner_user_id, $mqtt_user_type_id, $owner_username, $device_type_name, $device_username, &$json) {

    $topic = "device/". $device_type_name. "/". $device_username. "/#";

    if($this->does_device_to_user_mqtt_acl_exist($owner_user_id, $topic, $mqtt_user_type_id, $owner_username)) {
      $json["error_msg"][] = "device_to_user_mqtt_acl_aleady_exist";
    }
    else {
      // insert
      $this->the_connect_device_to_user_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username);
      $json["msg"][] = "device_to_user_mqtt_acl_now_exist";
    }
  }


  // 
  private function connect_device_to_user($device_id, $owner_user_id, &$json) {
    if($this->does_device_connect_to_user($device_id, $owner_user_id)) {
      $json["error_msg"][] = "user_already_connect_to_device";
    }
    else {
      $this->the_connect_device_to_user($device_id, $owner_user_id); 
      $json["msg"][] = "user_now_connect_to_device";
    } 
  }


  private function does_device_connect_to_user($device_id, $owner_user_id) {
    $user_device = \DB::table('user_device')
      ->where('user_id', $owner_user_id)
      ->where('device_id', $device_id)  
      ->first();

    if($user_device == NULL) {
      return false;
    }
    else {
      return true;
    }
  }
  

  private function the_connect_device_to_user($device_id, $owner_user_id) {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "user_id" => $owner_user_id,
      "device_id" => $device_id,
      "created_at" => date($format),  
      "updated_at" => date($format),
    );
    $inserted_id = \DB::table('user_device')->insertGetId($arr);
    return $inserted_id;
  }


  private function the_connect_device_to_user_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username) {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "user_id" => $owner_user_id,
      "allow" => 1,
      "access" => 3,
      "topic" => $topic,
      "created_at" => date($format),  
      "updated_at" => date($format),
      "mqtt_user_type_id" => $mqtt_user_type_id,
      "username" => $owner_username,
    );
    $inserted_id = \DB::table('mqtt_acl')->insertGetId($arr);
    return $inserted_id;
  }


  private function does_device_to_user_mqtt_acl_exist($owner_user_id, $topic, $mqtt_user_type_id, $owner_username) {
    $mqtt_acl = \DB::table('mqtt_acl')
      ->where('user_id', $owner_user_id)
      ->where('topic', $topic)
      ->where('mqtt_user_type_id', $mqtt_user_type_id)
      ->where('username', $owner_username)
      ->first();

    if($mqtt_acl == NULL) {
      return false;
    }
    else {
      return true;
    }
  }


  private function add_device_to_user_success_based_on_msg(&$json) {
    if(isset($json["error_msg"])) {
      $json["success"] = false;
    }
    else {
      $json["success"] = true;
    }
  }
    


  private function is_username_already_exist($username) {
    $user = \DB::table('users')     
      ->where('username', $username)
      ->first();    

    if($user != NULL) {
      return true;
    }
    else {
      // move to next
    }  

    $device = \DB::table('device')     
      ->where('username', $username)
      ->first();

    if($device != NULL) {
      return true;    
    }
    else {

    }

    return false;
  }


  private function is_email_already_exist($email) {
    $user = \DB::table('users')     
      ->where('email', $email)
      ->first();    

    if($user != NULL) {
      return true;
    }
    else {
      
    }  

    return false;
  }


  private function get_user_by_username($username, &$json) {
    $user = \DB::table('users')     
      ->where('username', $username)
      ->first();

    if($user == NULL) {
      $json["error_msg"][] = "user_not_in_db";
    }
    else {
      $json["msg"][] = "user_in_db";
    }

    return $user;
  }


  private function is_human_user_already_exist($username, &$json) {
    $user = \DB::table('users')     
      ->where('username', $username)
      ->first();

    if($user == NULL) {
      $json["error_msg"][] = "user_not_in_db";
      return false;
    }
    else {
      $json["msg"][] = "user_in_db";
      return true;
    }
  }


  // record token, update existing token or create a brand new token
  private function record_token($user_id, $identity, $info, $token, &$json) {
    if($this->is_username_identity_token_exist($user_id, $identity, $json)) {
      // we don't record, but we update
      $this->the_update_token($user_id, $identity, $token, $json);
    }
    else {
      $this->the_record_token($user_id, $identity, $info, $token, $json);
    }

  }


  private function the_record_token($user_id, $identity, $info, $token, &$json) {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "user_id" => $user_id,
      "identity" => $identity,
      "info" => $info,
      "token" => $token,
      "created_at" => date($format),
      "updated_at" => date($format),    
    );
    $user = \DB::table('user_identity_token')     
      ->insert($arr);

    $json["msg"][] = "token_record_created";
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


  private function local_get_token(
    $identity,
    $info,
    $user_id, 
    $username, 
    $password, 

    $encrypt_msg_mqtt_username, 
    $encrypt_msg_mqtt_password, 
    &$json
  ) {
    
    // Use actual username and password to gen token
    $credentials = array(
      "username" => $username,
      "password" => $password,
    );

    try {
      $issuer = "server";
      $subject = "user_auth";
      $audience = "app";
			$issue_at = time();
      $not_before = $issue_at; // cannot use nbf, why? 

      // You can set a very short time here,
      // but when it refreshes, it uses the jwt default setting.
      // so need to set to the same setting as default.
      $expire = $not_before + $this->token_ttl; 

      // https://scotch.io/tutorials/the-anatomy-of-a-json-web-token
			$claim = array(
        "iss" => $issuer,
        "sub" => $subject,
        "aud" => $audience,
				'iat' => $issue_at,
				'exp' => $expire,

        // Attach mqtt_username and mqtt_password to token
        'mqtt_username' => base64_encode($encrypt_msg_mqtt_username),
        'mqtt_password' => base64_encode($encrypt_msg_mqtt_password),
			);

      if (!$token = JWTAuth::attempt($credentials, $claim)) {
        $json["error_msg"][] = "invalid_credentials";
      }
    } 
		catch (JWTException $e) {
      $json["error_msg"][] = "could_not_create_token";
    }

    //
    $json["msg"][] = "token_is_created";
    $json["token"] = $token;

    // Store the actual token
    $this->record_token($user_id, $identity, $info, $token, $json);
	}


  private function is_my_token_valid(&$json) {
    $token = JWTAuth::getToken();
    
    // token empty
    if($token == false) {
      $json["error_msg"][] = "token_empty";
      $json["success"] = false;
      return false;
    }

    // It doesn't tell you whether you can refresh or not.
    try{
      // https://github.com/tymondesigns/jwt-auth/issues/297
      $payload = JWTAuth::getPayload($token);

      $token_validator = new TokenValidator();
      $payload_validator = new PayloadValidator();
      
      // by looking at the soruce code
      $is_token_well_formed = $token_validator->isValid($token);
      $is_payload_valid = $payload_validator->isValid($payload->toArray());

      if($is_token_well_formed) {
        if($is_payload_valid) {
          $json["msg"][] = "token_well_formed";
          $json["msg"][] = "payload_valid";
        }
        else {
          $json["error_msg"][] = "payload_not_valid";
          $json["success"] = false;
          return false;
        }
      }
      else {
        $json["error_msg"][] = "token_not_well_formed";
        $json["success"] = false;
        return false;
      }

    }
    catch(TokenBlacklistedException $e) {
      $json["error_msg"][] = "token_blacklisted";
      $json["success"] = false;
      return false;
    }
    catch (TokenInvalidException $e) {
      $json["error_msg"][] = "token_invalid";
      $json["success"] = false;
    	return false;
    }
    catch (TokenExpiredException $e) {
      $json["error_msg"][] = "token_expired";
      $json["success"] = false;
    	return false;
    }
    catch (JWTException $e) {
      $json["error_msg"][] = "jwt_exception";
      $json["success"] = false;
    	return false;
    }

    // We should let the public func to control success msg 
    //$json["success"] = true;
    return true;
  }


  private function gen_iv() {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    return $iv;
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
    $mqtt_password,

    $iv_mqtt_username,
    $iv_mqtt_password,
    &$json
  ) {
    // We only store mqtt_password, not actual user password.
    $hash_mqtt_password = hash("sha256", $mqtt_password);

    $format = "Y-m-d H:i:s";

    $arr = array(
      "username" => $mqtt_username,
      "encrypt_username" => $encrypt_mqtt_username,
      "other_encrypt_password" => $encrypt_mqtt_password,
      "password" => $hash_mqtt_password,

      "username_iv" => $iv_mqtt_username,
      "other_encrypt_password_iv" => $iv_mqtt_password,
      "created_at" => date($format),
      "updated_at" => date($format),
    );
    \DB::table("user_mqtt_password")
      ->insert($arr);

    $json["msg"][] = "insert_into_user_mqtt_password_done";
  }
  

  private function get_encrypt_mqtt_username_password_msg(
    $mqtt_username, 
    &$encrypt_mqtt_username_msg, 
    &$encrypt_mqtt_password_msg
  ) 
  {
    // Get obj base on mqtt_username
    $obj = \DB::table("user_mqtt_password")
      ->where('username', $mqtt_username)
      ->first();
    
    // get each component from row
    $encrypt_mqtt_username = $obj->encrypt_username;
    $encrypt_mqtt_password = $obj->other_encrypt_password;
    $iv_mqtt_username = $obj->username_iv;
    $iv_mqtt_password = $obj->other_encrypt_password_iv;

    // iv + encrypt_msg
    $encrypt_mqtt_username_msg = $iv_mqtt_username. $encrypt_mqtt_username;
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


  private function local_invalidate_token($token, &$json) {
    // NOTE: we don't do anything to the token in the database.

    try {
      JWTAuth::invalidate($token);
    }
    catch(TokenBlacklistedException $e) {
      $json["error_msg"][] = "token_already_blacklist";
    }
    catch (TokenInvalidException $e) {
      $json["error_msg"][] = "token_invalid";
    }
    catch (TokenExpiredException $e) {
      $json["error_msg"][] = "token_expired";
    }
    catch (JWTException $e) {
      $json["error_msg"][] = "jwt_exception";
    }

    //
    $json["msg"][] = "invalidate_token_done";
  }

  
  private function local_invalidate_token_by_user_id_and_identity($user_id, $identity, &$json) {
    $obj = \DB::table("user_identity_token")
      ->where("user_id", $user_id)
      ->where("identity", $identity)
      ->first();

    if($obj == NULL) {
      $json["error_msg"][] = "no_entry_in_user_identity_token";
    }
    else {
      $token = $obj->token;
      $this->local_invalidate_token($token, $json);
    }

  }


  // https://www.tutorialspoint.com/laravel/laravel_sending_email.htm
  // http://laravel-tricks.com/tricks/pass-variables-inside-the-mail-function
  private function mail_user_register_info($data) {
    // mail/user_register_info is blade template
    \Mail::send('mail/user_register_info', $data, function($message) use ($data) {
      $from_mail = $data["from_mail"];
      $from_name = $data["from_name"];
      $to_mail = $data["to_mail"];

      $user_defined_name = $data["user_defined_name"];
      $subject = $data["subject"];

      $message
        ->to($to_mail, $user_defined_name)
        ->subject($subject);
      $message->from($from_mail, $from_name);
    });
  }

  
  private function is_username_valid($username) {
    if (preg_match("/^[a-z0-9_-]{4,255}$/", $username)) {
      return true;
    }
    else {
      return false;
    } 
  }

}
