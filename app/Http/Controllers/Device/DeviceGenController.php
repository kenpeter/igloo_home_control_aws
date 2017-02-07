<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;


class DeviceGenController extends Controller
{
  // Hak
  public function __construct()
  {
    $this->middleware('auth');
  }

  public function device_gen_current_show($pattern_name)
  {
    $device_gen = \DB::table('device_gen AS gen')
      ->join("device_gen_pattern AS pattern", "gen.pattern_id", "=", "pattern.id")
      ->where("pattern.name", $pattern_name)
      ->select("gen.*")
      ->orderBy("gen.id", "desc")
      ->first();
 
    if($device_gen == null) {
      echo "{}";
    }
    else {
      echo json_encode($device_gen);
    }
  }


  public function device_gen_next_show($pattern_name)
  {
    $device_gen = \DB::table('device_gen')
      ->join('device_gen_pattern', 'device_gen.pattern_id', '=', 'device_gen_pattern.id')
      ->select('device_gen.*')
      ->get();

    if(is_array($device_gen)) {
      if(count($device_gen) > 0) {
        //
        $this->__device_gen_insert($pattern_name);
      }
      else {
        // no record
        $this->__device_gen_initial_insert($pattern_name);        
      }
    }
    else {

    }
  }


  // https://toothfi.local/device/gen/set_start_id/TST-5000/444
  public function device_gen_set_start_id($pattern_name, $start_inc_id) {
    $device_gen_pattern = \DB::table('device_gen_pattern')
      ->where("device_gen_pattern.name", $pattern_name)
      ->first();

    $device_gen_pattern_id = $device_gen_pattern->id;
    

    var_dump($device_gen_pattern);

    var_dump($pattern_name);    
    var_dump($start_inc_id);

  }


  private function __device_gen_insert($pattern_name) {
    $device_gen = \DB::table('device_gen AS gen')
      ->join("device_gen_pattern AS pattern", "gen.pattern_id", "=", "pattern.id")
      ->where("pattern.name", $pattern_name)
      ->first();

    $inc_id = $device_gen->inc_id;
    $name = $device_gen->name;
    $next_inc_id = $inc_id + 1;
    
    $format = "Y-m-d H:i:s";
    $unique_name = $name. $next_inc_id;
    $pattern_id = $device_gen->pattern_id;    

    // insert
    $tmp = array(
      "inc_id" => $next_inc_id,
      "pattern_id" => $pattern_id,
      "unique_name" => $unique_name,
      "created_at" => date($format),
      "updated_at" => date($format)
    );
    $inserted_id = \DB::table('device_gen')->insertGetId(
      $tmp
    );

    $tmp_array = array("unique_name" => $unique_name);
    echo json_encode($tmp_array);
  }

  
  private function __device_gen_initial_insert($pattern_name) {
    $device_gen_pattern = \DB::table('device_gen_pattern')
      ->where("name", $pattern_name)
      ->select('*')
      ->first();
    
    $pattern_id = $device_gen_pattern->id;
    $pattern = $device_gen_pattern->pattern;
    $start_inc_id = $device_gen_pattern->start_inc_id;
    $unique_name = $pattern. $start_inc_id;

    $format = "Y-m-d H:i:s";  

    // insert
    $tmp = array(
      "inc_id" => $start_inc_id,
      "pattern_id" => $pattern_id,
      "unique_name" => $unique_name,
      "created_at" => date($format),
      "updated_at" => date($format)
    );

    $inserted_id = \DB::table('device_gen')->insertGetId(
      $tmp
    );

    $tmp_array = array("unique_name" => $unique_name);

    echo json_encode($tmp_array);
  }  

}
