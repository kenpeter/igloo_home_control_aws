<?php

namespace App\Providers;

use App\Services\Validator;
use App\Services\MyUniqueUsernameValidator;
use Illuminate\Support\ServiceProvider;

class MyUniqueUsernameServiceProvider extends ServiceProvider {
  public function boot()
  {
    \Validator::resolver(function($translator, $data, $rules, $messages)
    {
      return new MyUniqueUsernameValidator($translator, $data, $rules, $messages);
    });
  }

  public function register()
  {

  }
}
