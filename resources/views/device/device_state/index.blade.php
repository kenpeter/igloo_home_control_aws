@extends('layouts.master')

@section('content')

<h1>Device State <a href="{{ url('device/device_state/create') }}" class="btn btn-primary pull-right btn-sm">Add New Device State</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Device name</th>
        <th>Current State Json</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    {{-- */$x=0;/* --}}
    @foreach($device_state as $item)
      {{-- */$x++;/* --}}
      <tr>
          <td><a href="{{ url('device/device_state', $item->id) }}">{{ $item->id }}</a></td>
          <td>{{ $item->device->name }}</td>
          <td>{{ $item->current_state_json }}</td>
          <td>
              <a href="{{ url('device/device_state/' . $item->id . '/edit') }}">
                  <button type="submit" class="btn btn-primary btn-xs">Update</button>
              </a> /
              {!! Form::open([
                  'method'=>'DELETE',
                  'url' => ['device/device_state', $item->id],
                  'style' => 'display:inline'
              ]) !!}
                  {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
              {!! Form::close() !!}
          </td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="pagination"> {!! $device_state->render() !!} </div>
</div>

@endsection
