<?php

namespace App\MyTrait;

// http://stackoverflow.com/questions/36657629/how-to-use-traits-laravel-5-2
trait TraitLib 
{
	// private $token_ttl = 60*60; // in seconds
	private $token_ttl = 60;
  private $secret_hash = "HAPVOiUTW!@y&W7#Z2P6XKNvXIu6Qaxt"; // 32 chars
  private $encryption_method = "AES-256-CBC";

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
  public function gen_strong_pass($length = 9, $add_dashes = false, $available_sets = 'luds')
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


  public function my_openssl_encrypt($msg, $iv) {
    $encrypt = openssl_encrypt($msg, $this->encryption_method, $this->secret_hash, 0, $iv);
    return $encrypt;
  }
  

  public function gen_iv() {
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    return $iv;
  }


  public function update_user_mqtt_password(
    $mqtt_username, 
    $encrypt_mqtt_username, 
    $encrypt_mqtt_password,  
    $hash_mqtt_password,

    $iv_mqtt_username, 
    $iv_mqtt_password, 
    $json
  ) 
  {
    $format = "Y-m-d H:i:s";

    $arr = array(
      "encrypt_username" => $encrypt_mqtt_username,
      "other_encrypt_password" => $encrypt_mqtt_password,
      "password" => $hash_mqtt_password,

      "username_iv" => $iv_mqtt_username,
      "other_encrypt_password_iv" => $iv_mqtt_password,
      "created_at" => date($format),
      "updated_at" => date($format),
    );
    \DB::table("user_mqtt_password")
      ->where("username", $mqtt_username)
      ->update($arr);

    $json["msg"][] = "insert_into_user_mqtt_password_done";
  }


  public function trait_lib_decrypt_msg_get_mqtt_username($encrypt_msg_mqtt_username, &$json) {
    // decode 64;
    $encrypt_msg_mqtt_username = base64_decode($encrypt_msg_mqtt_username);

    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC); // 16 bytes.
    $iv_mqtt_username = substr($encrypt_msg_mqtt_username, 0, $iv_size);
    $encrypt_msg_mqtt_username_rest = substr($encrypt_msg_mqtt_username, $iv_size);
    $mqtt_username_decrypt = openssl_decrypt($encrypt_msg_mqtt_username_rest, $this->encryption_method, $this->secret_hash, 0, $iv_mqtt_username);

    /*
    //test
    var_dump($encrypt_msg_mqtt_username);
    var_dump($iv_size);
    var_dump($iv_mqtt_username);
    var_dump($encrypt_msg_mqtt_username_rest);
    var_dump($this->encryption_method);
    var_dump($this->secret_hash);
    var_dump($mqtt_username_decrypt);
    die;
    */

    $json["msg"][] = "decrypt_mqtt_username_good";
    return $mqtt_username_decrypt;
  }

}
