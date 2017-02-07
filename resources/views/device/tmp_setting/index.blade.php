@extends('layouts.master')

@section('content')

<h1>Tmp setting <a href="{{ url('device/tmp_setting/create') }}" class="btn btn-primary pull-right btn-sm">Add New Tmp setting</a></h1>
<div class="table">
  <table class="table table-bordered table-striped table-hover">
    <thead>
      <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Value</th>
          <th>Device</th>
          <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    {{-- */$x=0;/* --}}
    @foreach($tmp_setting as $item)
      {{-- */$x++;/* --}}
      <tr>
        <td>{{ $item->id }}</td>
        <td><a href="{{ url('device/tmp_setting', $item->id) }}">{{ $item->name }}</a></td><td>{{ $item->value }}</td>
        <td>{{ $item->device->name }}</td>
        <td>
            <a href="{{ url('device/tmp_setting/' . $item->id . '/edit') }}">
                <button type="submit" class="btn btn-primary btn-xs">Update</button>
            </a> /
            {!! Form::open([
                'method'=>'DELETE',
                'url' => ['device/tmp_setting', $item->id],
                'style' => 'display:inline'
            ]) !!}
                {!! Form::submit('Delete', ['class' => 'btn btn-danger btn-xs']) !!}
            {!! Form::close() !!}
        </td>
      </tr>
    @endforeach
    </tbody>
    </table>
    <div class="pagination"> {!! $tmp_setting->render() !!} </div>
</div>

@endsection
