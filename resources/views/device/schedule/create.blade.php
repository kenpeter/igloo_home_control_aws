@extends('layouts.master')

@section('content')

<h1>Create New Schedule</h1>
<hr/>

{!! Form::open(['url' => 'device/schedule', 'class' => 'form-horizontal']) !!}
  <div class="form-group {{ $errors->has('day_code') ? 'has-error' : ''}}">
    {!! Form::label('day_code', 'Day Code: ', ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-6">
      {!! Form::number('day_code', null, ['class' => 'form-control']) !!}
      {!! $errors->first('day_code', '<p class="help-block">:message</p>') !!}
    </div>
  </div>

  <div class="form-group {{ $errors->has('time') ? 'has-error' : ''}}">
    {!! Form::label('time', 'Time: ', ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-6">
      {!! Form::text('time', null, ['class' => 'form-control']) !!}
      {!! $errors->first('time', '<p class="help-block">:message</p>') !!}
    </div>
  </div>
  
  <div class="form-group {{ $errors->has('command_json') ? 'has-error' : ''}}">
    {!! Form::label('command_json', 'Command Json: ', ['class' => 'col-sm-3 control-label']) !!}
    <div class="col-sm-6">
        {!! Form::textarea('command_json', null, ['class' => 'form-control']) !!}
        {!! $errors->first('command_json', '<p class="help-block">:message</p>') !!}
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
