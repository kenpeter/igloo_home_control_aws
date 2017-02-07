<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TmpSetting extends Model
{

  /**
   * The database table used by the model.
   *
   * @var string
   */
  protected $table = 'tmp_setting';

  /**
   * Attributes that should be mass-assignable.
   *
   * @var array
   */
  protected $fillable = ['name', 'value', 'device_id'];

  public function device()
  {
    return $this->belongsTo('App\Device');
  }

}
