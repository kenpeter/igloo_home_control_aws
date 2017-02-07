@extends('layouts.master')

@section('content')

<h1>Device <a href="{{ url('device/device/create') }}" class="btn btn-primary pull-right btn-sm">Add New Device</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Password</th>
        <th>Name</th>
        <th>Type</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {{-- */$x=0;/* --}}
      @foreach($device as $item)
        {{-- */$x++;/* --}}
        <tr>
          <td><a href="{{ url('device/device', $item->id) }}">{{ $item->id }}</a></td>
          <td>
            {{ $item->username }}
          </td>
          <td>{{ $item->password }}</td>
          <td>{{ $item->name }}</td>
          <td>{{ $item->device_type->name }}</td>  
          <td>
            <a href="{{ url('device/device/' . $item->id . '/edit') }}">
            <button type="submit" class="btn btn-primary btn-xs">Update</button>
            </a> /
              {!! Form::open([
                  'method'=>'DELETE',
                  'url' => ['device/device', $item->id],
                  'style' => 'display:inline'
              ]) !!}
                  {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
              {!! Form::close() !!}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <div class="pagination"> {!! $device->render() !!} </div>
   </div>
@endsection
