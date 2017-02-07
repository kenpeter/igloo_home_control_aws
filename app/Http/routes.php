<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// ----------------- Root -----------------------------------------------
Route::get('/', function () {
  // Auth::check()	Returns true if the current user is logged in, false otherwise.
  // Auth::guest()	Returns true if the current user is not logged in (a guest).
  // Auth::user()	Get the currently logged in user.

  // If already logged in
  if (Auth::guest()) {
    // https://stackoverflow.com/questions/20649725/laravel-4-redirect-homepage
    return view('welcome');
  }
  else {
    return view("home");
  }
});


// ----------------- Home -----------------------------------------------
// https://mattstauffer.co/blog/the-auth-scaffold-in-laravel-5-2
Route::group(['middleware' => 'web'], function () {
  // Need to login
  Route::auth();

  // Need to set auth in controller as well, to make it work
  Route::get('/home', 'HomeController@index');
});



// -------------------- With web auth ----------------------------
// https://laracasts.com/discuss/channels/requests/laravel-5-middleware-login-with-username-or-email
// with db user auth
Route::group(['middleware' => 'auth'], function()
{
  // Schedule, from CRUD generator
  Route::resource('device/schedule', 'Device\ScheduleController');	
  
  // Device , from CRUD generator
  Route::resource('device/device', 'Device\DeviceController');

  // Device type, from CRUD generator
  Route::resource('device/device_type', 'Device\DeviceTypeController');


  // More specific url needs to go to top,
  // because the generic one will process, if they are on top.
  // Expose json  
  Route::get('device/command/pending/json/{device_unique_name}/{command_id}', [
    'uses' => 'Device\CommandController@device_pending_single_command_json'
  ]);

  // https://toothfi.local/device/command/pending/json/IGTST
  Route::get('device/command/pending/json/{device_unique_name}', [
    'uses' => 'Device\CommandController@device_pending_command_json'
  ]);

  // https://toothfi.local/device/command/pending/IGTST/69/completed
  // Set a pending command completed
  Route::get('device/command/pending/{device_unique_name}/{command_id}/completed', [
    'uses' => 'Device\CommandController@device_set_pending_single_command_completed'
  ]);

  // Set all pending commands of a device completed.
  Route::get('device/command/pending/{device_unique_name}/completed', [
    'uses' => 'Device\CommandController@device_set_pending_command_completed'
  ]);

  // Set all pending commands of a device uncompleted.
  Route::get('device/command/pending/{device_unique_name}/uncompleted', [
    'uses' => 'Device\CommandController@device_set_pending_command_uncompleted'
  ]);

  Route::get('device/command/pending/{device_unique_name?}', [
    'uses' => 'Device\CommandController@device_pending_command'
  ]);

  Route::post('device/command/receive', [
    'uses' => 'Device\CommandController@device_receive_command'
  ]);

  // Command, from CRUD generator
  Route::resource('device/command', 'Device\CommandController');


  // all device state
  Route::resource('device/device_state', 'Device\DeviceStateController');

  // tmp setting
  Route::resource('device/tmp_setting', 'Device\TmpSettingController');

  // https://toothfi.local/device/device/IGTST/state/next/set/79
  Route::get('device/device/{device_unique_name}/state/next/set/{command_id}', [
    'uses' => 'Device\DeviceStateController@device_set_next_state_with_command'
  ]);

  Route::post('device/device/set_entire_state', [
    'uses' => 'Device\DeviceStateController@device_set_entire_state'
  ]);

  // https://toothfi.local/device/device/IGTST/state/latest/has
  Route::get('device/device/{device_unique_name}/state/latest/has', [
    'uses' => 'Device\DeviceStateController@is_device_has_latest_state'
  ]);

  // https://toothfi.local/device/device/IGTST/state/latest/json
  // get a device state in json
  Route::get('device/device/{device_unique_name}/state/latest/json', [
    'uses' => 'Device\DeviceStateController@device_latest_state_json'
  ]);

  // device gen current
  Route::get('device/gen/{pattern_name}/current/show', [
    'uses' => 'Device\DeviceGenController@device_gen_current_show'
  ]);

  // device gen next
  Route::get('device/gen/{pattern_name}/next/show', [
    'uses' => 'Device\DeviceGenController@device_gen_next_show'
  ]);

  // reset
  Route::get('device/gen/set_start_id/{pattern_name}/{start_inc_id}', [
    'uses' => 'Device\DeviceGenController@device_gen_set_start_id'
  ]);

});



// ---------------------- API with very simple auth -----------------------
// it use https stateless authentication
// with $user == 'rest_api_username' && $password == 'pideyRojwyt9' in http header
// https://www.getpostman.com/docs/helpers
Route::group(['middleware' => 'my_simple_api_auth'], function()
{
  // authenticate
  // https://toothfi.local/user/authenticate
  Route::post('user/authenticate', [
    'uses' => 'User\MyUserController@authenticate'
  ]);


  // name: test_android
  // username: test_android
  // email: test_android@test.com
  // password: password > 8 chars
  // register user api
  Route::post('user/my_account/register', [
    'uses' => 'User\MyUserController@user_account_register'
  ]);


  Route::post('user/my_account/my_reset_password', [
    'uses' => 'User\MyUserController@my_reset_password'
  ]);


  // device_type: smt_770
  // device_username: IGTST_108
  // mqtt_user_type: human
  // username: test_android
	// https://toothfi.local/user/relation/add_device_to_user
  Route::post('user/relation/add_device_to_user', [
    'uses' => 'User\MyUserController@add_device_to_user'
  ]);


  // device_type: smt_770
  // device_username: IGTST_108
  // mqtt_user_type: human
  // username: test_android
	// https://toothfi.local/user/relation/remove_device_from_user
  Route::post('user/relation/remove_device_from_user', [
    'uses' => 'User\MyUserController@remove_device_from_user'
  ]);


  // create zone
  // https://toothfi.local/zone/user_zone/create
  Route::post('zone/user_zone/create', [
    'uses' => 'Zone\MyZoneController@create'
  ]);


  // edit zone
  // https://toothfi.local/zone/user_zone/edit
  Route::post('zone/user_zone/edit', [
    'uses' => 'Zone\MyZoneController@edit'
  ]);

  
  // delete zone
  // https://toothfi.local/zone/user_zone/delete
  Route::post('zone/user_zone/delete', [
    'uses' => 'Zone\MyZoneController@delete'
  ]);


  // NOTE: different device_type will have different user interface.
  // device_name: my thermostat
  // device_type_name: Other type
  // device_username: IGTST_108
  // device_type: smt_770
  // 
  // device_serial_number: 12345678 is to identify the whole set of product. (unclear, not do)
  // dealer_username: xyz (unclear, not do)
  // https://toothfi.local/user_zone_device/create
  Route::post('device/user_zone_device/create', [
    'uses' => 'Device\DeviceController@user_zone_device_create'
  ]);
});



// ---------------- Use token to access --------------------
Route::group(['middleware' => 'web'], function()
{
  // It is actually authenticating
  // https://toothfi.local/user/token/get_token
  Route::post('user/token/get_token', [
    'uses' => 'User\MyUserTokenController@get_token'
  ]);


  // Refresh token
  // The same token can only refresh once.
  // https://toothfi.local/user/token/refresh_token
  Route::post('user/token/refresh_token', [
    'uses' => 'User\MyUserTokenController@refresh_token'
  ]);

  
  // https://toothfi.local/user/token/is_token_valid
  Route::post('user/token/is_token_valid', [
    'uses' => 'User\MyUserTokenController@is_token_valid'
  ]);


  // https://toothfi.local/user/token/invalidate_token
  Route::post('user/token/invalidate_token', [
    'uses' => 'User\MyUserTokenController@invalidate_token'
  ]);


  // device_type: smt_770
  // device_username: IGTST_108
  // mqtt_user_type: human
  // username: test_android
	// https://toothfi.local/user/token/relation/add_device_to_user
  Route::post('user/token/relation/add_device_to_user', [
    'uses' => 'User\MyUserTokenController@add_device_to_user'
  ]);
  

  // device_type: smt_770
  // device_username: IGTST_108
  // mqtt_user_type: human
  // username: test_android
	// https://toothfi.local/user/relation/remove_device_from_user
  Route::post('user/relation/remove_device_from_user', [
    'uses' => 'User\MyUserTokenController@remove_device_from_user'
  ]);


  // name: test_android
  // username: test_android
  // email: test_android@test.com
  // password: password > 8 chars
  // register user api
  Route::post('user/token/my_account/register', [
    'uses' => 'User\MyUserTokenController@user_account_register',
    'middleware' => 'my_simple_api_auth',
  ]);


  // with email
  Route::post('user/token/my_account/reset_password', [
    'uses' => 'User\MyUserTokenController@reset_password',
    'middleware' => 'my_simple_api_auth',
  ]);

  // get user info
  // https://toothfi.local/user/token/my_account/get_user_info
  Route::post('user/token/my_account/get_user_info', [
    'uses' => 'User\MyUserTokenController@get_user_info'
  ]);
  
});



// ---------------- Pure web access -----------------------
Route::group(['middleware' => ['web']], function () {
  // Hak
  // https://toothfi.local/user/my_authenticate
  // after get the token, visit with token https://toothfi.local/user/my_authenticate
  Route::group(['prefix' => 'user'], function()
  {
	  Route::resource('my_authenticate', 'MyAuthenticateController', ['only' => ['index']]);
	  Route::post('my_authenticate', 'MyAuthenticateController@authenticate');
  });


  // Hak
  // Authentication routes...
  // Notice that route is not starting with /, can do /login
  Route::get('auth/login', 'Auth\AuthController@getLogin');
  Route::post('auth/login', 'Auth\AuthController@postLogin');
  Route::get('auth/logout', 'Auth\AuthController@getLogout');


  // Hak
  // Registration routes...
  // http://stackoverflow.com/questions/35137768/how-to-use-postman-for-laravel-post-request
  Route::get('auth/register', 'Auth\AuthController@getRegister');
  Route::post('auth/register', 'Auth\AuthController@postRegister');


  // Hak
  Route::controllers([
    'password' => 'Auth\PasswordController',
  ]);


  // get device info based on mac address
  // e.g. http://toothfi.local/device/device/smt_770/600194005634/get_device_info
  // e.g. http://toothfi.local/device/device/heat_and_glo_rc300/88C25532EE86/get_device_info
	// password protected page, username: test, password: $41QLt04*4raRg 
  Route::get('device/device/{device_type}/{mac_address}/get_device_info', [
    'middleware' => 'auth.getdeviceinfo',
    'uses' => 'Device\DeviceController@get_device_info_based_on_mac'
  ]);


	// Duplicate here, why?
  Route::get('device/device/{device_type}/{mac_address}/get_device_info', [
    'uses' => 'Device\DeviceController@get_device_info_based_on_mac'
  ]);


	// http://toothfi.local/device/device/heat_and_glo_rc300/H&G-1216101/88C25532EE86/assign_device_name_to_mac
	// password protected page, username: test, password: $41QLt04*4raRg
	Route::get('device/device/{device_type}/{device_name}/{mac_address}/assign_device_name_to_mac', [
		'middleware' => 'auth.getdeviceinfo',
    'uses' => 'Device\DeviceController@assign_device_name_to_mac'
  ]);


	// Duplicate here, why?
	Route::get('device/device/{device_type}/{device_name}/{mac_address}/assign_device_name_to_mac', [
    'uses' => 'Device\DeviceController@assign_device_name_to_mac'
  ]);


  // https://toothfi.local/device/device/abc/get_fake_device_info
  Route::get('device/device/{device_name}/get_fake_device_info', [
    'uses' => 'Device\DeviceController@get_fake_device_info'
  ]);


  // Get firmware based on device_type and device_name
	// https://toothfi.com/device/device/a/b/c/get_firmware  
  Route::get('device/device/{device_type}/{device_name}/{firmware_type}/get_firmware', [
    'uses' => 'Device\DeviceController@get_firmware'
  ]);

  
  // test angular
  // https://toothfi.local/angular2/test/content
  Route::get('angular2/test/content', [
    'uses' => 'Angular2\TestController@angular2_test_content'
  ]);
});

