@extends('layouts.master')

@section('content')

<h1>Command</h1>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>command_timestamp</th>
        <th>command_json</th>
        <th>command_complete</th>
        <th>device</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $command->command_timestamp }}</td>
        <td>{{ $command->command_json }}</td>
        <td>{{ $command->command_complete }}</td>
        <td>{{ $command->device->name }}</td>
      </tr>
    </tbody>    
  </table>
</div>

@endsection
