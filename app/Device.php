<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'device';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['username', 'password', 'name', 'device_type_id'];


  public function schedules()
  {
    return $this->hasMany('App\Schedule');
  }

  public function commands()
  {
    return $this->hasMany('App\Command');
  }

  public function states()
  {
    return $this->hasMany('App\DeviceState');
  }

  public function tmpSettings()
  {
    return $this->hasMany('App\TmpSetting');
  }

  public function device_type()
  {
    return $this->belongsTo('App\DeviceType');
  }
}
