<?php

namespace App\Http\Controllers\Zone;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Zone;
use App\User;

class MyZoneController extends Controller
{
  // name (zone name)
  // my_long
  // my_lat
  // address
  // https://laravel.com/docs/5.2/requests
  // http://stackoverflow.com/questions/18214948/latitude-longitude-generation-to-be-used-as-sample-data
  public function create(Request $request) {
    // http://stackoverflow.com/questions/27873777/how-to-get-last-insert-id-in-eloquent-orm-laravel
    $zone = Zone::create($request->all());
    
    // attach zone to user
    // https://laravel.com/docs/5.2/eloquent
    $username = $request->input("username");
    $user = \DB::table("users")->where("username", $username)->first();

    if($user != NULL) {
      $format = "Y-m-d H:i:s";
      $arr = array(
        "user_id" => $user->id,
        "zone_id" => $zone->id,
        "created_at" => date($format),
        "updated_at" => date($format),
      );

      // insert
      \DB::table("user_zone")
        ->insert($arr);

      $json = array(
        "is_zone_created" => true,
        "zone_id" => $zone->id,
      );

      echo json_encode($json);
      return;
    }
    else {
      $json = array(
        "is_user_not_existed" => true,
      );

      echo json_encode($json);
      return;
    }
  }

  
  // name (zone name)
  // my_long
  // my_lat
  // address
  public function edit(Request $request) {
    $zone_id = $request->input("zone_id");
    
    if(!empty($zone_id)) {
      // https://laravel.com/docs/5.2/eloquent#basic-updates
      $zone = Zone::find($zone_id);

      if($zone != NULL) {
        $format = "Y-m-d H:i:s";

        $zone->name = $request->input("name");
        $zone->my_long = $request->input("my_long");
        $zone->my_lat = $request->input("my_lat");
        $zone->address = $request->input("address");
        $zone->updated_at = date($format);
        $zone->save();

        $json = array(
          "is_zone_edited" => true,
        );
      }
      else {
        $json = array(
          "no_such_zone" => true,
        );
      }  
    }
    else {
      $json = array(
        "zone_id_empty" => true,
      );
    }

    echo json_encode($json);
  }


  public function delete(Request $request) {
    $zone_id = $request->input("zone_id");
    
    // I don't need to delete user_zone here, since user_zone has 2 foreign keys
    // If a zone is deleted, that relationship will be deleted.
    if(!empty($zone_id)) {
      // https://laravel.com/docs/5.2/eloquent#basic-updates
      $zone = Zone::find($zone_id);

      if($zone != NULL) {
        $zone->delete();

        $json = array(
          "is_zone_deleted" => true,
        );
      }
      else {
        $json = array(
          "no_such_zone" => true,
        );
      }  
    }
    else {
      $json = array(
        "zone_id_empty" => true,
      );
    }

    echo json_encode($json);
  }

}
