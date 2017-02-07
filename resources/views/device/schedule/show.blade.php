@extends('layouts.master')

@section('content')

<h1>Schedule</h1>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID.</th>
        <th>Device Name</th>
        <th>Day Code</th>
        <th>Time</th>
        <th>Command Json</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $schedule->id }}</td>
        <td>{{ $schedule->device->name }}</td>
        <td>{{ $schedule->day_code }}</td>
        <td>{{ $schedule->time }}</td>
        <td>{{ $schedule->command_json }}</td>
      </tr>
    </tbody>    
  </table>
</div>

@endsection
