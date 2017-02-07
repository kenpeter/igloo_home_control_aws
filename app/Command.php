<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Command extends Model
{

  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'command';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['command_timestamp', 'command_json', 'command_complete', 'device_id'];

  public function device()
  {
    return $this->belongsTo('App\Device');
  }

  public function device_states()
  {
    return $this->hasMany('App\DeviceState');
  }
}
