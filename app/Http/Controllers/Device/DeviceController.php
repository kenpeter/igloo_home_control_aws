<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Device;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;


class DeviceController extends Controller
{
  use \App\MyTrait\TraitAssignMQTTACL;

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    //$device = Device::paginate(15);
    // device_type is func defined in Device model php
    $device = Device::with('device_type')->paginate(15);

    return view('device.device.index', compact('device'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    // https://stackoverflow.com/questions/17608160/laravel-eloquent-lists-sorting-a-list-of-column-values
    $device_types = \App\DeviceType::pluck('name', 'id');
    return view('device.device.create', compact('device_types'));
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {  
    $request['password'] = \Hash::make($request['password']);
  
    Device::create($request->all());

    Session::flash('flash_message', 'Device added!');

    return redirect('device/device');
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
    $device = Device::findOrFail($id);

    return view('device.device.show', compact('device'));
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
    $device = Device::with('device_type')->findOrFail($id);

    // https://stackoverflow.com/questions/17608160/laravel-eloquent-lists-sorting-a-list-of-column-values
    $device_types = \App\DeviceType::pluck('name', 'id');    

    return view('device.device.edit', compact('device', 'device_types'));
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
    $device = Device::findOrFail($id);

    // https://stackoverflow.com/questions/31490224/how-to-modify-request-values-in-laravel-5-1
    $request['password'] = \Hash::make($request['password']);

    $device->update($request->all());

    Session::flash('flash_message', 'Device updated!');

    return redirect('device/device');
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
    Device::destroy($id);

    Session::flash('flash_message', 'Device deleted!');

    return redirect('device/device');
  }


  public function get_device_info_based_on_mac($device_type, $mac_address) {
    // https://github.com/Intervention/httpauth
    \Httpauth::secure();

    $mac_address = $this->convert_mac_address($mac_address);

    $device_info_table = "device_info_". $device_type;    
    $json = array();    

    // https://laravel.com/docs/5.0/schema#checking-existence
    if (\Schema::hasTable($device_info_table))
    {
      // Assume mac address is unique, so pick it.
      $device_info = \DB::table($device_info_table)
        ->where("mac_address", "=", $mac_address)
        ->first();

			if(is_object($device_info)) {
      	$device_id = $device_info->device_id;
      	$device_plain_pass = $device_info->plain_password;
			}
			else {
				$device_id = "";
				$device_plain_pass = "";
			}


      if($device_id) {
        $device_obj = \App\Device::find($device_id);
        $device_username = $device_obj->username;

        // Don't output password, as too much for the device to parse json
        $json = array(
          "device_username" => $device_username,
          "device_plain_pass" => $device_plain_pass
          
        );
      }
      else {
        // https://stackoverflow.com/questions/8595627/best-way-to-create-an-empty-object-in-json-with-php
        $json = new \stdClass;
      }
    }
    else {
      $json = new \stdClass;
    }

    echo json_encode($json);
  }


	public function assign_device_name_to_mac($device_type, $device_name, $mac_address) {
		// https://github.com/Intervention/httpauth
    \Httpauth::secure();

		$device_info_table = "device_info_". $device_type;
    $json = array();

		// translate
		$mac_address = $this->convert_mac_address($mac_address);

		// https://laravel.com/docs/5.0/schema#checking-existence
    if(\Schema::hasTable($device_info_table))
    {
			// Assume mac address is unique, so pick it.
			// https://scotch.io/tutorials/debugging-queries-in-laravel
			// https://stackoverflow.com/questions/18236294/how-do-i-get-the-query-builder-to-output-its-raw-sql-query-as-a-string
      $device_info = \DB::table($device_info_table)
				->join('device', 'device.id', '=', $device_info_table.'.device_id')
				->where($device_info_table.'.mac_address', '=', $mac_address)
				->where('device.username', "=", $device_name)
				->select('device.id', $device_info_table.'.mac_address')
				->first();

				// existed
				if(is_object($device_info)) {
					if($device_info->id && $device_info->mac_address) {
						$json = array(
          		"name_map_mac" => "true"
        		);
					}
					else {
						// not there yet.
						$json = array(
              "msg" => "device_id_or_mac_not_existed"
            );
					}
				}
				else {
					// not there yet.
          $json = array(
          	"name_map_mac" => "just_map"
          );	

					// something to test
          // https://toothfi.local/device/device/heat_and_glo_rc300/H&G-4444444/DDDDDDDDDDDD/assign_device_name_to_mac  
          $this->create_device_and_map_mac($device_type, $device_name, $mac_address);
				}
		}
		else {
			$json = new \stdClass;
		}

		echo json_encode($json);
	}


  public function get_fake_device_info($device_name) {
    $json = array(
      "device_prop1" => "device_prop1",
      "device_prop2" => "device_prop2",
      "device_prop3" => "device_prop3"
      
    );

    echo json_encode($json);
  }


  public function get_firmware($device_type, $device_name, $firmware_type) {
		if($device_type == "smt_770") {
			$this->get_firmware_smt_770($device_type, $device_name, $firmware_type);
		}
		else if($device_type == "wifi_gateway") {
			$this->get_firmware_wifi_gateway($device_type, $device_name, $firmware_type);
		}
		else {
			echo "Unknown device_type and device_name: $device_type, $device_name, $firmware_type";
			die;
		}
  }


  public function user_zone_device_create(Request $request) {
    $device_name = $request->input("device_name");
    $device_type_name = $request->input("device_type_name");
  
    $device_username = $request->input("device_username");
    $device_type = $request->input("device_type");

    $owner_username = $request->input("owner_username");
    $zone_id = $request->input("zone_id");

    // check device_username
    if($this->is_device_username_exist($device_username, $device_id)) {

    }
    else {
      $json = array(
        "is_device_username_exist" => false,
      );
      echo json_encode($json);
      return;
    }
    
    // check device_type
    if($this->is_device_type_exist($device_type)) {

    }
    else {
      $json = array(
        "is_device_type_exist" => false,
      );
      echo json_encode($json);
      return;
    }

    // check owner_username
    if($this->is_owner_username_exist($owner_username, $owner_user_id)) {
      
    }
    else {
      $json = array(
        "is_owner_username_exist" => false,
      );
      echo json_encode($json);
      return;
    }

    // check zone
    if($this->is_zone_exist($zone_id)) {

    }
    else {
      $json = array(
        "is_zone_exist" => false,
      );
      echo json_encode($json);
      return;
    }    
    

    if($device_type == "smt_770") {
      // connect user to device
      if($this->connect_user_to_device($owner_user_id, $device_id)) {
        // able to connect user_to_device
      }
      else {
        // user and device already connected
      }      

      // connect zone to device
      // same zone can the same device multiple times.
      // same device can attach to different zones.
      if($this->connect_zone_to_device($zone_id, $device_id)) {
        // able to connect zone_to_device
      }
      else {
        // nothing here
      }

      // NOTE: because of deadline, we leave the perfect solution much laster.
      // assign mqtt acl
      // http://stackoverflow.com/questions/36657629/how-to-use-traits-laravel-5-2
      //$mqtt_user_type = "human";
      //$this->assign_mqtt_acl($owner_user_id, $device_type, $device_username, $mqtt_user_type);      

    }
    else {
      // other device type, so no assign mqtt acl
      
    }  
  }


	private function get_firmware_smt_770($device_type, $device_name, $firmware_type) {
		$file_root = "/var/www/html/laravel/misc/firmware/". $device_type;
		$file_name = "";
		$file_path = '';

		$special_arr = array(
			//"IGTST_108", // Gary test
			//"IGTST_109", // to smt_temp owner
      //"IGTST_115" // test it without web server
		);		

		if($firmware_type == "prod") {	
			if(in_array($device_name, $special_arr)) {
				$file_name = "smt_770_arduino_mod.ino.bin";
				$file_path = $file_root. "/". $file_name;
				$this->get_firmware_download($file_path);	
			}
			else {
        $file_name = "smt_770_arduino_web_mqtt.ino.bin";
      	$file_path = $file_root. "/". $file_name;
				$this->get_firmware_download($file_path);
			}
		}
		else if($firmware_type == "dev") {
			if(in_array($device_name, $special_arr)) {
        $file_name = "smt_770_arduino_mod.ino.bin";
        $file_path = $file_root. "/". $file_name;
        $this->get_firmware_download($file_path); 
      }
      else {
        //$file_name = "smt_770_arduino_mod.ino.dev.bin";
        $file_name = "smt_770_arduino_web_mqtt.ino.dev.bin";
        $file_path = $file_root. "/". $file_name;
        $this->get_firmware_download($file_path);
      }
		}
		else {
			echo "unknown firmware_type";
			die;
		}
	}

	private function get_firmware_wifi_gateway($device_type, $device_name, $firmware_type) {
		$file_root = "/var/www/html/laravel/misc/firmware/". $device_type;
    $file_name = "";
    $file_path = '';

    $special_arr = array(
      "set_me_up",
    );
   
		if($firmware_type == "prod") { 
    	if(in_array($device_name, $special_arr)) {
      	$file_name = "wifi_gateway.ino.bin";
      	$file_path = $file_root. "/". $file_name;
      	$this->get_firmware_download($file_path);
    	}
    	else {
      	$file_name = "wifi_gateway.ino.bin";
      	$file_path = $file_root. "/". $file_name;
      	$this->get_firmware_download($file_path);
    	}
		}
		else if($firmware_type == "dev") {
			if(in_array($device_name, $special_arr)) {
        $file_name = "wifi_gateway.ino.dev.bin";
        $file_path = $file_root. "/". $file_name;
        $this->get_firmware_download($file_path);
      }
      else {
        $file_name = "wifi_gateway.ino.dev.bin";
        $file_path = $file_root. "/". $file_name;
        $this->get_firmware_download($file_path);
      }
		}
		else {
			echo "unknown firmware_type";
      die;
		}

  }

	// https://stackoverflow.com/questions/4345322/how-can-i-allow-a-user-to-download-a-file-which-is-stored-outside-of-the-webroot
	private function get_firmware_download($file) {
		if (file_exists($file)) {
    	header('Content-Description: File Transfer');
    	header('Content-Type: application/octet-stream');
    	header('Content-Disposition: attachment; filename='.basename($file));
    	header('Content-Transfer-Encoding: binary');
    	header('Expires: 0');
    	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    	header('Pragma: public');
    	header('Content-Length: ' . filesize($file));
    	ob_clean();
    	flush();
    	readfile($file);
    	exit;
		}
		else {
			echo "no such file: $file";
			die;
		}
	}
	
  private function convert_mac_address($mac_addr, $separator = ':') {
    if(strpos($mac_addr, $separator) !== false) {
      // assume in the right format
      return $mac_addr;
    }
    else {
      // convert mac_addr from 600194005634 to 60:01:94:00:56:34
      // http://www.stetsenko.net/2011/01/php-mac-address-validating-and-formatting/
      return join($separator, str_split($mac_addr, 2));
    }
  }


	private function create_device_and_map_mac($device_type, $device_name, $mac_address) {
		$device_id = $this->create_device($device_type, $device_name);
		$this->map_mac($device_type, $device_id, $mac_address);
	}


	private function create_device($device_type, $device_name) {
		$device_id = "";

		//var_dump($device_type); // heat_and_glo_rc300
		//var_dump($device_name); // H&G-1216101

		// insert
		$device_type_id = \DB::table("device_type")->where('name', '=', $device_type)->first()->id;	
		$format = "Y-m-d H:i:s";

		// https://laracasts.com/discuss/channels/general-discussion/db-insertgetid
		$device_id = \DB::table("device")->insertGetId(
      array(
        "username" => $device_name,
				// hardcode, no..........
        // plain password" => y5+|qDbLrE
        "password" => "fd51a2eeb1209fec393ab7a3d9bea68a2a6cdfe40d6a8be7011bf5a9fce74c41",
        "name" => $device_name,
        "created_at" => date($format),
        "updated_at" => date($format),
        "device_type_id" => $device_type_id
      )
    );

		return $device_id;
	}


	private function map_mac($device_type, $device_id, $mac_address) {
		//var_dump($device_id); // 1234 e.g.
    //var_dump($mac_address); // DD:DD:DD:DD:DD:DD

		$format = "Y-m-d H:i:s";
		$device_info_table = "device_info_". $device_type; 

    \DB::table($device_info_table)->insert(
      array(
        "device_id" => $device_id,
        "mac_address" => $mac_address,
        "created_at" => date($format),
        "updated_at" => date($format),
				// hardcode, no......
        "plain_password" => "y5+|qDbLrE"
      )
    );
	
	}


  private function is_device_username_exist($device_username, &$device_id) {
    $device = \DB::table("device")
      ->where("username", $device_username)
      ->first();
    
    if($device == NULL) {
      return false;    
    }
    else {
      $device_id = $device->id;
      return true;
    }
  }

  
  private function is_device_type_exist($device_type) {
    $device_type = \DB::table("device_type")
      ->where("name", $device_type)
      ->first();
    
    if($device_type == NULL) {
      return false;    
    }
    else {
      return true;
    }
  }
  

  private function is_owner_username_exist($owner_username, &$owner_user_id) {
    $owner_user = \DB::table("users")
      ->where("username", $owner_username)
      ->first();
    
    if($owner_user == NULL) {
      return false;    
    }
    else {
      $owner_user_id = $owner_user->id;
      return true;
    }
  }


  private function is_zone_exist($zone_id) {
    $zone = \DB::table("zone")
      ->where("id", $zone_id)
      ->first();
    
    if($zone == NULL) {
      return false;    
    }
    else {
      return true;
    }
  }

  
  private function connect_user_to_device($owner_user_id, $device_id) {
    $user_device = \DB::table("user_device")
      ->where("user_id", $owner_user_id)
      ->where("device_id", $device_id)
      ->first();

    if($user_device == NULL) {
      // insert
      $format = "Y-m-d H:i:s";  

      $arr = array(
        "user_id" => $owner_user_id,
        "device_id" => $device_id,
        "created_at" => date($format),
        "updated_at" => date($format),
      );
      $user_device = \DB::table("user_device")
        ->insert($arr);      

      return true;
    }
    else {
      // user already has that device
      return false;
    }
  }


  // This allows the same zone, can have the same devices twice, thrid times ...
  private function connect_zone_to_device($zone_id, $device_id) {
    /*
    $device_zone = \DB::table("device_zone")
      ->where("device_id", $device_id)
      ->where("zone_id", $zone_id)
      ->first();
    */

    /*
    if($device_zone == NULL) {
      // insert
      $format = "Y-m-d H:i:s";  

      $arr = array(
        "device_id" => $device_id,
        "zone_id" => $zone_id,
        "created_at" => date($format),
        "updated_at" => date($format),
      );
      $device_zone = \DB::table("device_zone")
        ->insert($arr);      

      return true;
    }
    else {
      // zone already has that device
      return false;
    }
    */

    // This allows the same zone, can have the same devices twice, thrid times ...
    // insert
    $format = "Y-m-d H:i:s";  

    $arr = array(
      "device_id" => $device_id,
      "zone_id" => $zone_id,
      "created_at" => date($format),
      "updated_at" => date($format),
    );
    \DB::table("device_zone")
      ->insert($arr);      

    return true;

  }

}
