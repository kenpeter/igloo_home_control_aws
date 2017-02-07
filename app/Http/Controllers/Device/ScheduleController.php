<?php

namespace App\Http\Controllers\Device;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Schedule;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Session;

class ScheduleController extends Controller
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
    //$schedule = Schedule::paginate(15);

    // https://stackoverflow.com/questions/34423319/joining-model-with-table-laravel-5-2
    // https://stackoverflow.com/questions/14480510/relationship-and-blade-in-laravel
    $schedule = Schedule::with('device')->paginate(15);

    return view('device.schedule.index', compact('schedule'));
  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    $devices = \App\Device::pluck('name', 'id');

    return view('device.schedule.create', compact('devices'));
  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Request $request)
  {  
    Schedule::create($request->all());

    Session::flash('flash_message', 'Schedule added!');

    return redirect('device/schedule');
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
    $schedule = Schedule::findOrFail($id);

    return view('device.schedule.show', compact('schedule'));
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
    //$schedule = Schedule::findOrFail($id);
    // https://stackoverflow.com/questions/29165410/how-to-join-three-table-by-laravel-eloquent-model
    $schedule = Schedule::with('device')->findOrFail($id);
    $devices = \App\Device::pluck('name', 'id');

    return view('device.schedule.edit', compact('schedule', 'devices'));
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
    $schedule = Schedule::findOrFail($id);
    $schedule->update($request->all());

    Session::flash('flash_message', 'Schedule updated!');

    return redirect('device/schedule');
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
    Schedule::destroy($id);

    Session::flash('flash_message', 'Schedule deleted!');

    return redirect('device/schedule');
  }

}
