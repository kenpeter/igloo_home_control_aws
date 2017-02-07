@extends('layouts.master')

@section('content')

<h1>device_type <a href="{{ url('device/device_type/create') }}" class="btn btn-primary pull-right btn-sm">Add New device_type</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      {{-- */$x=0;/* --}}
      @foreach($device_type as $item)
        {{-- */$x++;/* --}}
        <tr>
          <td><a href="{{ url('device/device_type', $item->id) }}">{{ $item->id }}</a></td>
          <td>{{ $item->name }}</td>
          <td>
            <a href="{{ url('device/device_type/' . $item->id . '/edit') }}">
              <button type="submit" class="btn btn-primary btn-xs">Update</button>
            </a> /
            {!! Form::open([
                'method'=>'DELETE',
                'url' => ['device/device_type', $item->id],
                'style' => 'display:inline'
            ]) !!}
                {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  <div class="pagination"> {!! $device_type->render() !!} </div>
</div>

@endsection
