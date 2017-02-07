<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class MyUserController extends Controller
{
  // https://laracasts.com/discuss/channels/general-discussion/laravel-52-re-using-password-reset-functionality
  use \Illuminate\Foundation\Auth\ResetsPasswords;

  // NOTE: password needs to >= 8 chars long in laravel 5
  public function user_account_register(Request $request) {
    $format = "Y-m-d H:i:s";

    $name = $request->input("name");
    $email = $request->input("email");
    $username = $request->input("username");
    $password = $request->input("password");

    // guard username in user and device table    
    if($this->is_username_already_exist($username)) {
      $json = array();
      $json["error_msg"][] = "username_already_exist";
      $json["success"] = false;
      echo json_encode($json);
      return;    
    }
    else {

    }

    // guard email
    if($this->is_email_already_exist($email)) {
      $json = array();
      $json["error_msg"][] = "email_already_exist";
      $json["success"] = false;  
      echo json_encode($json);
      return;
    }
    else {

    }

    // guard password
    if(strlen($password) < 8) {
      $json = array();
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

    //
    $json = array();
    $json["msg"][] = "user_is_created";
    $json["username"] = $username;
    $json["user_id"] = $user_id;
    $json["hash_password"] = $hash_password;
    $json["success"] = true;
  
    echo json_encode($json);
  }


  public function authenticate(Request $request)
  {
    $username = $request->input("username");
    $password = $request->input("password");

    $json = array();

    if(!empty($username) && !empty($password)) {
      $sha_256_pass = hash("sha256", $password);

      $user = \DB::table('users')
        ->where('username', '=', $username)
        ->where('password', '=', $sha_256_pass)
        ->first();

      if($user == NULL) {
        //
        $json = array();
        $json["error_msg"][] = "username_and_password_not_match";
        $json["success"] = false;
      }
      else {
        // Good
        $json = array();
        $json["msg"][] = "username_and_password_match";
        $json["success"] = true;
      }
    }
    else {
      //
      $json = array();
      $json["error_msg"][] = "username_or_password_empty";
      $json["success"] = false;
    }

    echo json_encode($json);
  }


  // https://laracasts.com/discuss/channels/general-discussion/laravel-52-re-using-password-reset-functionality
  public function my_reset_password(Request $request) {
    $email = $request->input("email");

    if(!empty($email)) {
      $request->merge(['email' => $email]);
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
      $json["msg"][] = "reset_password_email_sent";
      $json["success"] = true;
      echo json_encode($json);
      return;
    }

  }  
  

  // NOTE: entry point
  public function add_device_to_user(Request $request) {
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
      // -------- connect device to user ------------
      $this->connect_device_to_user($device_id, $owner_user_id, $json);


      // ------ connect user and device in mqtt acl -------
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
      $json["msg"][] = "device_to_user_mqtt_acl_aleady_exist";
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
      $json["msg"][] = "user_already_connect_to_device";
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
    if(isset($json["msg"])) {
      if(
        in_array("user_already_connect_to_device", $json["msg"]) &&
        in_array("device_to_user_mqtt_acl_aleady_exist", $json["msg"])
      ) {
        $json["success"] = false;    
      }
      else {
        $json["success"] = true;
      }
    }
    else {
      $json["success"] = false;
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
}
