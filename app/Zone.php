<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'zone';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name', 
    'my_long', 
    'my_lat',
    'address'
  ];


  public function devices()
  {
    return $this->hasMany('App\Device');
  }

  public function users()
  {
    return $this->belongsTo('App\User');
  }
}
