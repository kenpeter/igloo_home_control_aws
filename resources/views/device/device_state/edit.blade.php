@extends('layouts.master')

@section('content')

<h1>Edit Device State</h1>
<hr/>

{!! Form::model($device_state, [
  'method' => 'PATCH',
  'url' => ['device/device_state', $device_state->id],
  'class' => 'form-horizontal'
]) !!}

<div class="form-group {{ $errors->has('device_type') ? 'has-error' : ''}}">
  {!! Form::label('device_id', 'Device: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {{ Form::select('device_id', $devices) }}
    {!! $errors->first('device_id', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('current_state_json') ? 'has-error' : ''}}">
{!! Form::label('current_state_json', 'Current State Json: ', ['class' => 'col-sm-3 control-label']) !!}
<div class="col-sm-6">
  {!! Form::textarea('current_state_json', null, ['class' => 'form-control']) !!}
  {!! $errors->first('current_state_json', '<p class="help-block">:message</p>') !!}
</div>
</div>


<div class="form-group">
  <div class="col-sm-offset-3 col-sm-3">
    {!! Form::submit('Update', ['class' => 'btn btn-primary form-control']) !!}
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
