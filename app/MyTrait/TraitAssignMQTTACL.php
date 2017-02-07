<?php

namespace App\MyTrait;

// http://stackoverflow.com/questions/36657629/how-to-use-traits-laravel-5-2
trait TraitAssignMQTTACL {

  public function assign_mqtt_acl($owner_user_id, $device_type, $device_username, $mqtt_user_type) {
    
    die;


    // e.g. device_type == smt_770
    // $device_username == IGTST_108
    // $mqtt_user_type == human
    // $owner_username == test_android

    $device_type = $request->input("device_type");
    $device_username = $request->input("device_username");
    $mqtt_user_type = $request->input("mqtt_user_type");
    $username = $request->input("username");

    // device type
    $device_type = \DB::table('device_type')->where('name', $device_type)->first();
    if($device_type == NULL) {
      return false;
    }
    else {
      
    }

    // device
    $device = \DB::table('device')->where('username', $device_username)->first();
    if($device == NULL) {
      return false;
    }
    else {
      $device_id = $device->id;
    }
    
    // mqtt user type
    $mqtt_user_type_obj = \DB::table('mqtt_user_type')->where('name', $mqtt_user_type)->first();
    if($mqtt_user_type_obj == NULL) {
      return false;
    }
    else {
      $mqtt_user_type_id = $mqtt_user_type_obj->id;
    }

    // owner username
    $owner_user = \DB::table('users')->where('username', $owner_username)->first();
    if($owner_user == NULL) {
      return false;
    }
    else {
      $owner_user_id = $owner_user->id;
    }

    // topic
    $topic = "device/". $device_type->name. "/". $device_username. "/#";

    // 
    if($this->does_mqtt_acl_exist($owner_user_id, $topic, $mqtt_user_type_id, $owner_username)) {
      // already there
    }
    else {
      // insert
      $this->the_assign_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username);
    }
    
    return true;
  } 
}


/*
public function does_mqtt_acl_exist($owner_user_id, $topic, $mqtt_user_type_id, $owner_username) {
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
*/

/*
public function the_assign_mqtt_acl($owner_user_id, $topic, $mqtt_user_type_id, $owner_username) {
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
  \DB::table('mqtt_acl')->insert($arr);
}
*/
