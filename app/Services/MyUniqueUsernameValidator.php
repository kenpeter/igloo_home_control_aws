<?php 

namespace App\Services;

use Illuminate\Validation\Validator;
use Carbon\Carbon;

// Hak
// http://stackoverflow.com/questions/28417977/custom-validator-in-laravel-5
class MyUniqueUsernameValidator extends Validator {
  public function validateMyUniqueUsername($attribute, $value, $parameters)
  {
    $condi = false;

    if($attribute == "username") {
      // Inspect username in table laravel_devices
      // 'username' => 'required|unique:users|my_unique_username'
      // we already check users table
      if(!empty($value)) {
        $user = \DB::table('device')->where('username', '=', $value)->get();

        if(empty($user)) {
          $condi = true;
        }
        else {
          
        }
      }
      else {
        
      }
    }
    else {
      // do nothing
    }
    return $condi;
  }
}
