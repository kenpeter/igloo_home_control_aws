<?php

// https://stackoverflow.com/questions/17710897/how-to-use-sha1-encryption-instead-of-bcrypt-in-laravel-4
namespace App\Libraries;

use Illuminate\Support\ServiceProvider;

class SHAHashServiceProvider extends ServiceProvider 
{

  /**
   * Register the service provider.
   *
   * @return void
   */
  public function register() {
    $this->app['hash'] = $this->app->share(function () {
        return new SHAHasher();
    });
  }

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides() {
    return array('hash');
  }

}
