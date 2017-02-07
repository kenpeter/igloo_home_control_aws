@extends('layouts.master')

@section('content')
  <h1>Device type</h1>
  <div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID.</th> <th>Name</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $device_type->id }}</td> <td> {{ $device_type->name }} </td>
      </tr>
    </tbody>    
    </table>
  </div>

@endsection
