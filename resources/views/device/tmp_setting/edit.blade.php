@extends('layouts.master')

@section('content')

<h1>Edit Tmp setting</h1>
<hr/>

{!! Form::model($tmp_setting, [
    'method' => 'PATCH',
    'url' => ['device/tmp_setting', $tmp_setting->id],
    'class' => 'form-horizontal'
]) !!}

<div class="form-group {{ $errors->has('name') ? 'has-error' : ''}}">
  {!! Form::label('name', 'Name: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
    {!! $errors->first('name', '<p class="help-block">:message</p>') !!}
  </div>
</div>

<div class="form-group {{ $errors->has('value') ? 'has-error' : ''}}">
  {!! Form::label('value', 'Value: ', ['class' => 'col-sm-3 control-label']) !!}
  <div class="col-sm-6">
    {!! Form::text('value', null, ['class' => 'form-control']) !!}
    {!! $errors->first('value', '<p class="help-block">:message</p>') !!}
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
