<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Command;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class CommandController extends Controller 
{
  // Hak
  public function __construct()
  {
    $this->middleware('auth');
  }

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $command = Command::with("device")->paginate(15);

    return view('device.command.index', compact('command'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    $devices = \App\Device::pluck('name', 'id');
    return view('device.command.create', compact('devices'));
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {  
    Command::create($request->all());

    Session::flash('flash_message', 'Command added!');

    return redirect('device/command');
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   *
   * @return Response
   */
  public function show($id)
  {
    $command = Command::findOrFail($id);

    return view('device.command.show', compact('command'));
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   *
   * @return Response
   */
  public function edit($id)
  {
    $command = Command::findOrFail($id);
    $devices = \App\Device::pluck('name', 'id');

    return view('device.command.edit', compact('command', "devices"));
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  int  $id
   *
   * @return Response
   */
  public function update($id, Request $request)
  {
      
    $command = Command::findOrFail($id);
    $command->update($request->all());

    Session::flash('flash_message', 'Command updated!');

    return redirect('device/command');
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   *
   * @return Response
   */
  public function destroy($id)
  {
    Command::destroy($id);

    Session::flash('flash_message', 'Command deleted!');

    return redirect('device/command');
  }


  // https://stackoverflow.com/questions/34406943/sending-json-objects-to-laravel-via-post
  
  public function device_receive_command(Request $request) {
    $device_unique_name = $request->input('device_unique_name');
    $command_json = $request->input('command_json');

    if($device_unique_name && $command_json) {      
      $device_id = $this->__get_device_id_by_device_username($device_unique_name);
      $device_type = $this->__get_device_type_by_device_unique_name($device_unique_name);      
      
      $command_json = $this->__convert_data_structure_with_command($device_type, $command_json);

      
      // create a command
      $tmp_array = array(
        "command_timestamp" => \DB::raw('UTC_TIMESTAMP()'),
        "command_json" => $command_json,
        "command_complete" => 0,
        "device_id" => $device_id,
        "created_at" => \DB::raw('UTC_TIMESTAMP()'),
        "updated_at" => \DB::raw('UTC_TIMESTAMP()')
      );

      $command_id = \DB::table('command')
        ->insertGetId($tmp_array);          


      // feedback
      $command_json = json_decode($command_json);
      $return_json = array(
        "device_unique_name" => $device_unique_name,
        "command_json" => $command_json,
        "command_id" => $command_id
      );

      // feedback
      echo json_encode($return_json);      

    }
    else {
      echo "missing device_unique_name or command_json";
    }
  }
  

  
  // https://toothfi.local/device/command/pending/IGTST
  public function device_pending_command($device_unique_name = NULL) {

    if($device_unique_name) {
      $device_id = $this->__get_device_id_by_device_username($device_unique_name);

      $pending_commands = Command::with('device')
        ->where('device_id', $device_id)
        ->where('command_complete', 0)
        ->get();        

      return view('device.command.pending', compact('command', "pending_commands"));
    }
    else {
      $pending_commands = Command::with('device')
        ->where('command_complete', 0)
        ->get();
      return view('device.command.pending', compact('command', "pending_commands"));
    }
  }


  // Output json for a single pending command
  public function device_pending_single_command_json($device_unique_name, $command_id) {
    $this->__device_pending_single_command_json($device_unique_name, $command_id);    
  }


  // Output json for pending commands
  public function device_pending_command_json($device_unique_name) {
    $this->__device_pending_command_json($device_unique_name);
  }

  // Set a pending command of a device completed
  public function device_set_pending_single_command_completed($device_unique_name, $command_id) {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);

    $res = \DB::table('command')
      ->where('device_id', $device_id)
      ->where('id', $command_id)
      ->where('command_complete', 0)
      ->update(
        array('command_complete' => 1)
      );
  }

  // set all pending command of a device completed.
  public function device_set_pending_command_completed($device_unique_name) {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);

    $res = \DB::table('command')
      ->where('device_id', $device_id)
      ->where('command_complete', 0)
      ->update(
        array('command_complete' => 1)
      );
  }

  // set all pending command of a device completed.
  public function device_set_pending_command_uncompleted($device_unique_name) {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);

    $res = \DB::table('command')
      ->where('device_id', $device_id)
      ->where('command_complete', 1)
      ->update(
        array('command_complete' => 0)
      );
  }
  

  private function __get_device_id_by_device_username($device_unique_name) {
    $device_id = \DB::table("device")
      ->where('username', $device_unique_name)
      ->first()
      ->id;

    return $device_id;
  }

  private function device_delete_command($device_unique_name, $command_id) {
    $command_ids = preg_split("/,+/", $command_id);

    $device_id = \DB::table("device")
      ->where('username', $device_unique_name)
      ->first()
      ->id;

    //test
    /*
    var_dump("----");
    var_dump($device_id);
    var_dump($device_unique_name);
    var_dump($command_id);
    */


    // command complete set to 1, so what is 2
    foreach ($command_ids as $command_id) {

      $res = \DB::table('command')
        ->where('device_id', $device_id)
        ->where('id', $command_id)
        ->where('command_complete', 0)
        ->update(
          array('command_complete' => 1)
        ); 
    }            

  }
  

  private function __device_pending_single_command_json($device_unique_name, $command_id) {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);  
    $device_type = $this->__get_device_type_by_device_unique_name($device_unique_name);

    //test
    //var_dump($device_id);

    // only pull oldest 5 pending commands
    $res = \DB::table("command")
      ->select("id", "command_json", "created_at")
      ->where("id", $command_id)
      ->where("device_id", $device_id)
      ->where("command_complete", 0)
      ->orderBy('command_timestamp', 'asc')
      ->take(5)
      ->get();

    // convert
    $res = $this->__convert_data_structure_with_result($device_type, $res);

    foreach($res as $item) {
      // A single array, containing propertie objects
      $json_arr = json_decode($item->command_json);
      
      $obj = new \stdClass;
      $obj->created_at = $item->created_at;
      array_unshift($json_arr, $obj);    
    }

    echo json_encode($json_arr);
  }

  private function __device_pending_command_json($device_unique_name) {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);  
    $device_type = $this->__get_device_type_by_device_unique_name($device_unique_name);

    // only pull oldest 5 pending commands
    $res = \DB::table("command")
      ->select("id", "command_json", "created_at AS stored_at")
      ->where("device_id", $device_id)
      ->where("command_complete", 0)
      ->orderBy('stored_at', 'asc')
      ->take(5)
      ->get();

    // convert
    $res = $this->__convert_data_structure_with_result($device_type, $res);

    $command_array = array();
    foreach($res as $item) {
      // A single array, containing propertie objects
      $json_arr = json_decode($item->command_json);

      // we create an object and push into array.
      //$obj = new \stdClass;
      // Don't use it for now
      //$obj->stored_at = $item->stored_at;  
      //$obj->command_id = $item->id;
      //array_unshift($json_arr, $obj);

      array_push($command_array, $json_arr);     
    } 

    echo json_encode($command_array);
  }
  

  private function device_check_schedule($device_unique_name) {
    // This another schedule check.
    $affected_num = $this->__device_check_schedule($device_unique_name);
    if($affected_num == 1)
		{
			//echo "$device_unique_name: run the schedule";
			$this->process_schedule($device_unique_name);
		}
		else
		{
			//echo "Already run";
		}
  }

  // On a particular day, every minute to it grab command in the schedule table
  // Because command in schedule table, never expire
  // so it keeps injecting pending command into the command table.
  private function __device_check_schedule($device_unique_name) {
    // http://laraveldaily.com/did-you-know-affected-rows-after-eloquent-update/
    $device_id = \DB::table("device")
      ->where('username', $device_unique_name)
      ->first()
      ->id;

    $affected_num = \DB::table('tmp_setting')
      ->where('name', 'last_schedule')
      ->where('device_id', $device_id)
      ->whereRaw('UNIX_TIMESTAMP(NOW()) > (UNIX_TIMESTAMP(value)+60)')
      // https://stackoverflow.com/questions/28161786/how-to-save-now-to-the-same-field-in-my-laravel-eloquent-model-every-time-i-sa
      ->update(
        array('value' => \DB::raw('UTC_TIMESTAMP()'))
      );

    //test
    /*
    var_dump("---");
    var_dump($affected_num);
    */

    return $affected_num;
  }


  private function process_schedule($device_unique_name) {
    // utc
		date_default_timezone_set('UTC');

    $timeStr = date('H:i'); // e.g. 19:42
    
    //test
    //var_dump($timeStr);

    $daycode = date('N'); // e.g. 1, 2, 3, etc
    
    //test
    //var_dump($daycode);
    //echo("Weekday Daycode: ". $daycode. " Time:". $timeStr. "</br></br>");


    // device_id    
    $device_id = \DB::table("device")
      ->where('username', $device_unique_name)
      ->first()
      ->id;


    // -----------------------------  
    // NOTE !!!!!!!
    // -----------------------------
    // AND time < ? change it back to time = ?
    // After each date pass, daycode is chaning, need to update daycode
    // in schedule table as well.

    // If it is time in schedule
    // put the command into comamnd table, in pending state
    // This is like a condition check
    if(intval($daycode) > 5) {
  
      $res = \DB::table('schedule')
        ->select(\DB::raw(
          '
            id,
            device_id,
            command_json
          '
        ))
        ->where('device_id', $device_id)
        ->whereRaw('
          (
            day_code = 0 OR 
            day_code = 9 OR 
            day_code = ? 
          )
        ', 
          array($daycode)
        )
        ->where('time', "=", $timeStr)
        ->get();

    }
    else {
      $res = \DB::table('schedule')
        ->select(\DB::raw(
          '
            id,
            device_id,
            command_json
          '
        ))
        ->where('device_id', $device_id)
        ->whereRaw('
          (
            day_code = 0 OR 
            day_code = 8 OR 
            day_code = ? 
          )
        ', 
          array($daycode)
        )
        ->where('time', "=", $timeStr)
        ->get();    
    }

    //test
    /*
    var_dump("-- schedule --");
    var_dump($res);
    */

    foreach($res as $item) {
      $device_id = $item->device_id;
      $command_json = $item->command_json;

      // Keep injecting pending command.
      $this->queue_json_command($device_id, $command_json);
    }


    // So here we force to clean up the pending command every 10 min
    // Any command over 10 min, and not yet completed, need to set it 2

    //test
    //var_dump("-- clean up after 10 min --");

    $tmp_array = array(
      "command_timestamp" => \DB::raw('UTC_TIMESTAMP()'),
      "command_complete" => 2,
      "updated_at" => \DB::raw('NOW()')
    );
    $res = \DB::table('command')
      ->where("command_complete", 0)
      ->WhereRaw("UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(command_timestamp) > 600")
      ->update($tmp_array);
 

    //test
    //var_dump("--- after 10 mins ---");
    //var_dump($res);
  }

  
  private function queue_json_command($device_id, $command_json) {
    /*
    var_dump("----");
    var_dump($device_id);
    var_dump($command_json);
    */

    // create a command
    $tmp_array = array(
      "command_timestamp" => \DB::raw('UTC_TIMESTAMP()'),
      "command_json" => $command_json,
      "command_complete" => 0,
      "device_id" => $device_id,
      "created_at" => \DB::raw('UTC_TIMESTAMP()'),
      "updated_at" => \DB::raw('UTC_TIMESTAMP()')
    );
    $res = \DB::table('command')
      ->insert($tmp_array);
  }
  

  private function __get_device_type_by_device_unique_name($device_unique_name) {
    $device_type = \DB::table("device_type")
      ->join('device', 'device_type.id', '=', 'device.device_type_id')
      ->where('device.username', $device_unique_name)
      ->select('device_type.name')
      ->first() // return object
      ->name;
    return $device_type;
  } 


  private function __convert_data_structure_with_result($device_type, $result) {
    if($device_type == config('constants.wifi_thermo.str')) {
      $this->__convert_smt_770_data_structure_with_result($result);
    }
    else {
      // do nothing
    }

    return $result;
  }



  private function __convert_data_structure_with_command($device_type, $command_json) {
    if($device_type == config('constants.wifi_thermo.str')) {
      $command_json = $this->__convert_smt_770_data_structure_with_command($command_json);

    }
    else {
      // do nothing
    }

    return $command_json;
  } 


  /*
    {
      "state": [
        {
          "key": "thermo_mode",
          "value": {
            "addr": "40002",
            "val": "1"
          }
        }
      ]
    }

    to

    {
      "cmd": {
        "addr": "40002",
        "val": "1"
      }
    }

  */


  private function __convert_smt_770_data_structure_with_result(&$result) {
    foreach($result as &$item) {
      // Only interested on command json
      $command_obj = json_decode($item->command_json);
      $state = $command_obj->state;

      $addr = $state->value->addr;
      $val = $state->value->val;

      $arr = array();
      
      $arr["cmdId"] = $item->id;
      $arr["cmd"] = array(
        "addr" => $addr,
        "val" => $val
      );

      $json = json_encode($arr);
      $item->command_json = $json;

      /*
      $state = $command_obj->state;

      $lega_state = array(); 
      foreach($state as $prop) {
        // $prop->key
        $prop_value = $prop->value;
        $lega_state[] = $prop_value;
      }

      $lega_state = json_encode($lega_state);
      $item->command_json = $lega_state;
      */   

    }


  }
  

  /* 
    convert the old structure

    {
      "cmd": {
        "addr": "40002",
        "val": "1"
      }
    }

    to 

    {
      "state": [
        {
          "key": "thermo_mode",
          "value": {
            "addr": "40002",
            "val": "1"
          }
        }
      ]
    }
  */

  private function __convert_smt_770_data_structure_with_command($command_json) {
    // $command_json is json string
    // only one command at a time
    $command_obj = json_decode($command_json);
    $addr = $command_obj->cmd->addr;
    $val = $command_obj->cmd->val;
    
    $key = $this->__interpret_smt_770_data_addr($addr);

    $result = array();
    $new_command_arr = array();    

    $new_command_arr["key"] = $key;
    $new_command_arr["value"] = array(
      "addr" => $addr,
      "val" => $val
    );

    $result["state"] = $new_command_arr;
    $result = json_encode($result);

    return $result;    
  }
  

 
  private function __interpret_smt_770_data_addr($lega_addr) {
    $key = "";
    if($lega_addr == "1") {
      $key = config('constants.wifi_thermo.heat_or_cool_str');
    }
    elseif($lega_addr == "40002") {
      $key = config('constants.wifi_thermo.thermo_mode_str');
    }
    elseif($lega_addr == "40003") {
      $key = config('constants.wifi_thermo.fan_mode_str');
    }
    elseif($lega_addr == "40011") {
      $key = config('constants.wifi_thermo.thermo_mode_str');
    }
    elseif($lega_addr == "40012") {
      $key = config('constants.wifi_thermo.day_heat_set_temp_str');
    }
    elseif($lega_addr == "40013") {
      $key = config('constants.wifi_thermo.night_cool_set_temp_str');
    }
    elseif($lega_addr == "40014") {
      $key = config('constants.wifi_thermo.night_heat_set_temp_str');
    }
    elseif($lega_addr == "40015") {
      $key = config('constants.wifi_thermo.single_temp_set_manual_str');
    }
    elseif($lega_addr == "40054") {
      $key = config('constants.wifi_thermo.day_night_mode_str');
    }
    elseif($lega_addr == "40354") {
      $key = config('constants.wifi_thermo.curr_temp_celsius_str');
    }
    elseif($lega_addr == "40355") {
      $key = config('constants.wifi_thermo.curr_temp_fahrenheit_str');
    }
    else {
      $key = config('constants.wifi_thermo.unknown');
    }

    return $key;
  }
  


}
