@extends('layouts.master')

@section('content')

<h1>Create New Command</h1>
<hr/>

{!! Form::open(['url' => 'device/command', 'class' => 'form-horizontal']) !!}

<div class="form-group {{ $errors->has('command_timestamp') ? 'has-error' : ''}}">
  {!! Form::label('command_timestamp', 'command_timestamp: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::text('command_timestamp', null, ['class' => 'form-control']) !!}
    {!! $errors->first('command_timestamp', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('command_json') ? 'has-error' : ''}}">
  {!! Form::label('command_json', 'command_json: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::textarea('command_json', null, ['class' => 'form-control']) !!}
    {!! $errors->first('command_json', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('name') ? 'has-error' : ''}}">
  {!! Form::label('command_complete', 'command_complete: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
      {!! Form::text('command_complete', null, ['class' => 'form-control']) !!}
      {!! $errors->first('command_complete', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('device') ? 'has-error' : ''}}">
  {!! Form::label('device', 'Device: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {{ Form::select('device_id', $devices) }}
    {!! $errors->first('device', '<p class="help-block">:message</p>') !!}
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
