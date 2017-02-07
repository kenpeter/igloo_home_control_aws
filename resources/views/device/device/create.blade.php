@extends('layouts.master')

@section('content')

<h1>Create New Device</h1>
<hr/>

{!! Form::open(['url' => 'device/device', 'class' => 'form-horizontal']) !!}

<div class="form-group {{ $errors->has('username') ? 'has-error' : ''}}">
  {!! Form::label('username', 'Username: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::text('username', null, ['class' => 'form-control']) !!}
    {!! $errors->first('username', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('password') ? 'has-error' : ''}}">
  {!! Form::label('password', 'Password (hashed after created): ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::text('password', null, ['class' => 'form-control']) !!}
    {!! $errors->first('password', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('name') ? 'has-error' : ''}}">
  {!! Form::label('name', 'Name: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
      {!! Form::text('name', null, ['class' => 'form-control']) !!}
      {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('device_type') ? 'has-error' : ''}}">
  {!! Form::label('device_type', 'Device type: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {{ Form::select('device_type_id', $device_types) }}
    {!! $errors->first('device_type', '<p class="help-block">:message</p>') !!}
  </div>
</div>


<div class="form-group">
  <div class="col-sm-offset-3 col-sm-3">
      {!! Form::submit('Create', ['class' => 'btn btn-primary form-control']) !!}
  </div>
</div>
{!! Form::close() !!}

@if ($errors->any())
  <ul class="alert alert-danger">
    @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
    @endforeach
  </ul>
@endif

@endsection
