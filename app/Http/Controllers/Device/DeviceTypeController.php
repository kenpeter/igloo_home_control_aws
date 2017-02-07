<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\DeviceType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class DeviceTypeController extends Controller
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
    $device_type = DeviceType::paginate(15);
    return view('device.device_type.index', compact('device_type'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    return view('device.device_type.create');
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {
    DeviceType::create($request->all());
    Session::flash('flash_message', 'device_type added!');
    return redirect('device/device_type');
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
    $device_type = DeviceType::findOrFail($id);
    return view('device.device_type.show', compact('device_type'));
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
    $device_type = DeviceType::findOrFail($id);
    return view('device.device_type.edit', compact('device_type'));
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
    $device_type = DeviceType::findOrFail($id);
    $device_type->update($request->all());

    Session::flash('flash_message', 'device_type updated!');

    return redirect('device/device_type');
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
    DeviceType::destroy($id);
    Session::flash('flash_message', 'device_type deleted!');
    return redirect('device/device_type');
  }

}
