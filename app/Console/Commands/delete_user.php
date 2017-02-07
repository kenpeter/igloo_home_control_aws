<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

// http://goodheads.io/2015/12/18/how-to-create-a-custom-artisan-command-in-laravel-5/
class delete_user extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'delete_user';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'delete_user......';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
      parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {
    $json = array();
    $this->delete_user($json);
  }


  private function get_certain_username() {
    /*
    $arr = array(
      "iostest2",
      "iostest3",
      "iostest4",
      "iostest5",
      "iostest6",
      "iostest7",
    );
    */

    $arr = array(
      "gliang"
    );    

    return $arr;
  }


  private function delete_user(&$json) {
    // https://github.com/goodheads/custom-artisan-command/blob/master/app/Console/Commands/Faces.php
    //$this->comment("hihi");

    $usernames = $this->get_certain_username();
    foreach($usernames as $username) {
      $user_obj = \DB::table("users")
        ->where("username", $username)
        ->first();

      if($user_obj == NULL) {
        // No such user
        $json["error_msg"][] = "$username | no_such_user";
      }
      else {
        // 
        $user_id = $user_obj->id;
        $this->delete_actual_user($username, $json);
        $this->delete_mqtt_acl_by_username($username, $json);
        $this->delete_user_mqtt_password_by_username($username, $json);

        // user_device, etc no need to delete, as it uses user_id
      }
    }

    // http://stackoverflow.com/questions/33075714/artisan-error-logging-verbose-level
    // http://www.easylaravelbook.com/blog/2015/09/04/logging-an-array-in-laravel/
    // output
    $this->comment(print_r($json));
  }


  private function delete_actual_user($username, &$json) {
    \DB::table("users")
      ->where("username", $username)
      ->delete();
    $json["msg"][] = "$username | user_delete";
  }

  
  private function delete_mqtt_acl_by_username($username, &$json) {
    \DB::table("mqtt_acl")
      ->where("username", $username)
      ->delete();
    $json["msg"][] = "$username | mqtt_acl_delete";
  }


  private function delete_user_mqtt_password_by_username($username, &$json) {
    \DB::table("user_mqtt_password")
      ->where("username", $username)
      ->delete();
    $json["msg"][] = "$username | user_mqtt_password_delete";
  }
  
}
