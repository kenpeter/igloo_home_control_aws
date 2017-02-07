<?php

namespace App\Listeners;

use App\Events\PasswordWasReset;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ListenerPasswordWasReset
{
  /**
   * Create the event listener.
   *
   * @return void
   */
  public function __construct()
  {
      //
  }

  /**
   * Handle the event.
   *
   * @param  PasswordWasReset  $event
   * @return void
   */
  public function handle(PasswordWasReset $event)
  {
      //
  }
}
