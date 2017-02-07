<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{

  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'device_type';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['name'];


  public function device()
  {
    return $this->hasMany('App\Device');
  }
}
