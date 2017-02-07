@extends('layouts.master')

@section('content')

<h1>Pending command <a href="{{ url('device/command/create') }}" class="btn btn-primary pull-right btn-sm">Add New Command</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>command_timestamp</th>
        <th>command_json</th>
        <th>command_complete</th>
        <th>Device</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {{-- */$x=0;/* --}}
      @foreach($pending_commands as $item)
        {{-- */$x++;/* --}}
        <tr>
          <td>
            <a href="{{ url('device/command', $item->id) }}">{{ $item->id }}</a>  
          </td>
          <td>
            {{ $item->command_timestamp }}
          </td>
          <td>{{ $item->command_json }}</td>
          <td>{{ $item->command_complete }}</td>
          <td>{{ $item->device->name }}</td>  
          <td>
            <a href="{{ url('device/command/' . $item->id . '/edit') }}">
            <button type="submit" class="btn btn-primary btn-xs">Update</button>
            </a> /
              {!! Form::open([
                  'method'=>'DELETE',
                  'url' => ['device/command', $item->id],
                  'style' => 'display:inline'
              ]) !!}
                  {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
              {!! Form::close() !!}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    
   </div>
@endsection
