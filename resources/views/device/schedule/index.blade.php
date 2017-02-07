@extends('layouts.master')

@section('content')

<h1>Schedule <a href="{{ url('device/schedule/create') }}" class="btn btn-primary pull-right btn-sm">Add New Schedule</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Device Name</th>
        <th>Day Code</th>
        <th>Time</th>
        <th>Command Json</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {{-- */$x=0;/* --}}
      @foreach($schedule as $item)
        {{-- */$x++;/* --}}
        <tr>
          <td><a href="{{ url('device/schedule', $item->id) }}">{{ $item->id }}</a></td>
          <td>
            {{ $item->device->name }}  
          </td>
          <td>
            {{ $item->day_code }}
          </td>
          <td>
            {{ $item->time }}
          </td>
          <td>
            {{ $item->command_json }}
          </td>
          <td>
            <a href="{{ url('device/schedule/' . $item->id . '/edit') }}">
              <button type="submit" class="btn btn-primary btn-xs">Update</button>
            </a> /
            {!! Form::open([
                'method'=>'DELETE',
                'url' => ['device/schedule', $item->id],
                'style' => 'display:inline'
            ]) !!}
                {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="pagination"> {!! $schedule->render() !!} </div>  
</div>

@endsection
