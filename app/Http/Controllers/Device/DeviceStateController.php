<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\DeviceState;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class DeviceStateController extends Controller
{
  // Hak
  public function __construct()
  {
    $this->middleware('auth');
  }

  public function index()
  {
    $device_state = DeviceState::with('device')->paginate(15);
    return view('device.device_state.index', compact('device_state'));
  }

  public function create()
  {
    $devices = \App\Device::pluck('name', 'id');
    return view('device.device_state.create', compact('devices'));
  }

  public function store(Request $request)
  {   
    DeviceState::create($request->all());

    Session::flash('flash_message', 'Device state added!');

    return redirect('device/device_state');
  }

  public function show($id)
  {
    $device_state = DeviceState::findOrFail($id);

    return view('device.device_state.show', compact('device_state'));
  }

  public function edit($id)
  {
    $device_state = DeviceState::findOrFail($id);
    $devices = \App\Device::pluck('name', 'id');    

    return view('device.device_state.edit', compact('device_state', 'devices'));
  }

  public function update($id, Request $request)
  {  
    $device_state = DeviceState::findOrFail($id);

    $device_state->update($request->all());

    Session::flash('flash_message', 'Device updated!');

    return redirect('device/device_state');
  }

  public function destroy($id)
  {
    DeviceState::destroy($id);

    Session::flash('flash_message', 'Device deleted!');

    return redirect('device/device_state');
  }


  // https://toothfi.local/device/device/IGTST/state/next/set/77
  public function device_set_next_state_with_command($device_unique_name, $command_id) {
    $device_state_latest = $this->device_latest_state_json($device_unique_name);

    $device = $this->__get_device_by_device_username($device_unique_name);
    $device_id = $device->id;
    $device_type = $device->device_type->name;
    $command_json = $this->__get_command_by_command_id($command_id)->command_json;

    $this->__device_set_next_state_with_command($device_id, $device_type, $device_state_latest, $command_id, $command_json);

  }


  public function device_set_entire_state(Request $request) {
    $device_unique_name = $request->input('device_unique_name');
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);

    $command_id = $request->input('command_id');

    $device_state = $request->input('state');

  
    //test
    /*
    var_dump("var_dump");    
    var_dump($device_unique_name);    
    var_dump($command_id);
    var_dump($device_state);
    */


    // insert into db
    $tmp_array = array(
      "device_id" => $device_id,
      "command_id" => $command_id,
      "current_state_json" => $device_state,
      "created_at" => \DB::raw('UTC_TIMESTAMP()'),
      "updated_at" => \DB::raw('UTC_TIMESTAMP()')
    );

    $device_state_id = \DB::table('device_state')
      ->insertGetId($tmp_array);
  
    $json = array(
      "device_state_id" => $device_state_id
    );

    echo json_encode($json);

  }

  
  public function is_device_has_latest_state($device_unique_name) {
    $device_state_latest = $this->device_latest_state_json($device_unique_name);

    // why string null?
    if(!($device_state_latest === 'null')) {
      // need to be string
      return "true";
    }
    else {
      return "false";
    }
  }


  public function device_latest_state_json($device_unique_name)
  {
    $device_id = $this->__get_device_id_by_device_username($device_unique_name);

    $device_state = DeviceState
      ::where("device_id", $device_id)
      ->orderBy('created_at', 'desc')
      ->get()
      ->first();

    if($device_state) {
      $current_state_json = $device_state->current_state_json;     
    }
    else {
      $current_state_json = '';
    }

    // Clean up some format issue, so need to decode and encode.
    $current_state_json = json_decode($current_state_json);
    $current_state_json = json_encode($current_state_json);

    return $current_state_json;
  }


  /*
  private function __device_set_next_state_with_command($device_id, $device_type, $device_state_latest, $command_id, $command_json) {
    if($device_type == 'smt_770') {
      $this->__device_smt_770_set_next_state_with_command($device_id, $device_state_latest, $command_id, $command_json);
    }
    else {
      // Default device_type
      
    }
  }
  */


  /*
    var current_device_state = 
    {
      created_at: "2016-04-22 05:25:47", // This is UTC time.
      state: [
        {
          key: "is_heating",
          value: {
            type: "1",
            addr: "5",
            val: "0"
          }
        },  
        
        {
          key: "is_cooling",
          value: {
            type: "1",
            addr: "4",
            val: "0"
          }
        },

        {
          key: "thermo_mode",
          value: {
            type: "3",
            addr: "40002",
            val: "0"
          }
        },
        
        {
          key: "fan_mode",
          value: {
            type: "3",
            addr: "40003",
            val: "0"
          }
        },

        {
          key: "curr_temp_celsius",
          value: {
            type: "3",
            addr: "40354",
            val: "600" // (raw_value_celsius - 400) / 10 = 20 degrees
          }
        },

        {
          key: "curr_temp_fahrenheit",
          value: {
            type: "3",
            addr: "40355",
            val: "600" // (raw_value_fahrenheit - 400) / 10 = 20 fahrenheit
          }
        },

        {
          key: "single_temp_set_manual",
          value: {
            type: "3",
            addr: "40015",
            val: "60"
          }
        }      
      ]
    };

    var command = 
    {
      created_at: "2016-04-22 06:25:47", // This is UTC time.
      state: [
        {
          key: "is_heating",
          value: {
            type: "1",
            addr: "5",
            val: "1"
          }
        },  

        {
          key: "thermo_mode",
          value: {
            type: "3",
            addr: "40002",
            val: "2"
          }
        }
      ]
    };


  */


  private function __device_set_next_state_with_command($device_id, $device_type, $device_state_latest, $command_id, $command_json) {
    if($device_state_latest && $command_json) {
      $device_state_latest = json_decode($device_state_latest);
      $command_json = json_decode($command_json);

      $device_state = $device_state_latest->state;
      $command_state = $command_json->state;

      // Merge command json and latest state.
      $new_state = array();
      if(is_array($device_state)) {
        foreach($device_state as $device_prop) {

          $new_state[] = $device_prop;
          foreach($command_state as $command_prop) {
            $device_key = $device_prop->key;
            $command_key = $command_prop->key;
            if($device_key == $command_key) {
              $last = count($new_state);
              $new_state[$last-1] = $command_prop;
              break; 
            }
            else {
              // no change
            }   
          }
        }
      }

      /*
      // NOTE: cannot use this, as need to know the structure of json, to compare
      // https://stackoverflow.com/questions/6472183/php-get-difference-of-two-arrays-of-objects
      $diff = array_udiff($new_state, $device_state,
        function ($a, $b) {
          $condi_1 = $a->type - $b->type;
          $condi_2 = $a->addr - $b->addr;
          $condi_3 = $a->val - $b->val;

          if($condi_1 == 0 && $condi_2 == 0 && $condi_3 == 0) {
            $condi = 0;
          }
          else {
            $condi = 1;
          }

          return $condi;
        }
      );
      */

      // Add time stamp
      // http://www.pontikis.net/tip/?id=18
      $new_state = array(
        "created_at" => date('Y-m-d H:i:s'),
        "state" => $new_state
      );

      // create new state entry in device_state
      $new_device_state = new \App\DeviceState;
      $new_device_state->device_id = $device_id;
      $new_device_state->current_state_json = json_encode($new_state);
      $new_device_state->command_id = $command_id;
      $new_device_state->save();    

      echo json_encode(array(
        "device_state" => "created"
      ));
    }
    else {
      // device has no latest state
      echo json_encode(array(
        "device_state" => "no_latest_state"
      ));
    }
  }


  /*
  private function __device_smt_770_set_next_state_with_command($device_id, $device_state_latest, $command_id, $command_json) {
    if($device_state_latest && $command_json) {
      $device_state_latest = json_decode($device_state_latest);
      $command_json = json_decode($command_json);

      $device_state = $device_state_latest->state;
      $command_state = $command_json->state;

      
      //var command = {
      //  created_at: "2016-04-22 05:25:47", // This is UTC time.
      //  state: [
      //    {
      //      type: "3",
      //      addr: "40002",
      //      val: "4"
      //    },
      //    {
      //      type: "3",
      //      addr: "40003",
      //      val: "4"
      //    }
      //  ]
      //};
      

      
       // var device_state_on_device = 
       // {
       //  created_at: "2016-04-22 05:25:47", // This is UTC time.
       //   state: [
       //     {
       //       type: "1",
       //       addr: "4",
       //       val: "0"
       //     },
       //     {
       //       type: "1",
       //       addr: "4",
       //       val: "0"
       //     },
       //     {
       //       type: "3",
       //       addr: "40002",
       //       val: "2"
       //     },
       //     {
       //       type: "3",
       //       addr: "40003",
       //       val: "3"
       //     },
       //     {
       //       type: "3",
       //       addr: "40354",
       //       val: "600"
       //     },
       //     {
       //       type: "3",
       //       addr: "40355",
       //       val: "600"
       //     },
       //     {
       //       type: "3",
       //       addr: "40015",
       //       val: "60"
       //     }
       //   ]
       // };
      

      // Merge command json and latest state.
      $new_state = array();
      if(is_array($device_state)) {
        foreach($device_state as $device_prop) {

          $new_state[] = $device_prop;
          foreach($command_state as $command_prop) {
            $device_addr = $device_prop->addr;
            $command_addr = $command_prop->addr;
            if($device_addr == $command_addr) {
              $last = count($new_state);
              $new_state[$last-1] = $command_prop;
              break; 
            }
            else {
              // no change
            }   
          }
        }
      }

      // https://stackoverflow.com/questions/6472183/php-get-difference-of-two-arrays-of-objects
      $diff = array_udiff($new_state, $device_state,
        function ($a, $b) {
          $condi_1 = $a->type - $b->type;
          $condi_2 = $a->addr - $b->addr;
          $condi_3 = $a->val - $b->val;

          if($condi_1 == 0 && $condi_2 == 0 && $condi_3 == 0) {
            $condi = 0;
          }
          else {
            $condi = 1;
          }

          return $condi;
        }
      );

      
      //var_dump("diff");
      //var_dump(count($diff));
      

      // Need to compare new_state and device_state_latest?
      if($diff) {

        // Add time stamp
        // http://www.pontikis.net/tip/?id=18
        $new_state = array(
          "created_at" => date('Y-m-d H:i:s'),
          "state" => $new_state
        );

        
        // create new state entry in device_state
        $new_device_state = new \App\DeviceState;
        $new_device_state->device_id = $device_id;
        $new_device_state->current_state_json = json_encode($new_state);
        $new_device_state->command_id = $command_id;
        $new_device_state->save();    

        echo json_encode(array(
          "device_state" => "created"
        ));
      }
      else {
        echo json_encode(array(
          "device_state" => "no_change"
        ));
      }
    }
    else {
      // device has no latest state
      echo json_encode(array(
        "device_state" => "no_latest_state"
      ));
    }
    
  }
  */

  private function __get_device_id_by_device_username($device_unique_name) {
    $device_id = \App\Device::where('username', $device_unique_name)->first()->id;
    return $device_id;
  }

  private function __get_device_by_device_username($device_unique_name) {
    $device = \App\Device::where('username', $device_unique_name)->first();
    return $device;
  }

  private function __get_command_by_command_id($command_id) {
    $device = \App\Command::where('id', $command_id)->first();
    return $device; 
  }

}
