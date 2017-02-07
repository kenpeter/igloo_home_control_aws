<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\TmpSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class TmpSettingController extends Controller
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
    // $tmp_setting = TmpSetting::paginate(15);
    $tmp_setting = TmpSetting::with('device')->paginate(15);  
    
    return view('device.tmp_setting.index', compact('tmp_setting'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    $devices = \App\Device::pluck('name', 'id');
    return view('device.tmp_setting.create', compact('devices'));
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {
      
      Tmp_Setting::create($request->all());

      Session::flash('flash_message', 'Tmp Setting added!');

      return redirect('device/tmp_setting');
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
      $tmp_setting = TmpSetting::findOrFail($id);

      return view('device.tmp_setting.show', compact('tmp_setting'));
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
    $tmp_setting = TmpSetting::findOrFail($id);
    $devices = \App\Device::pluck('name', 'id');

    return view('device.tmp_setting.edit', compact('tmp_setting', 'devices'));
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
      
      $tmp_setting = TmpSetting::findOrFail($id);
      $tmp_setting->update($request->all());

      Session::flash('flash_message', 'Tmp Setting updated!');

      return redirect('device/tmp_setting');
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
      TmpSetting::destroy($id);

      Session::flash('flash_message', 'Tmp Setting deleted!');

      return redirect('device/tmp_setting');
  }

}
