<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceState extends Model
{
  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'device_state';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['device_id', 'command_id', 'current_state_json'];

  public function device()
  {
    return $this->belongsTo('App\Device');
  }

  public function device_state()
  {
    return $this->belongsTo('App\DeviceState');
  }

}
