@extends('layouts.master')

@section('content')

<h1>Device</h1>
<div class="table-responsive">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID.</th> <th>Username</th><th>Password</th><th>Name</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $device->id }}</td>
        <td>{{ $device->username }}</td>
        <td>{{ $device->password }}</td>
        <td>{{ $device->name }}</td>
      </tr>
    </tbody>    
  </table>
</div>

@endsection
