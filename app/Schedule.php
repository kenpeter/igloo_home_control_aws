<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'schedule';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['day_code', 'time', 'command_json', 'device_id'];


  public function device()
  {
    return $this->belongsTo('App\Device');
  }

}
