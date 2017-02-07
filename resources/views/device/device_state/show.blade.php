@extends('layouts.master')

@section('content')

    <h1>Device state</h1>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>ID.</th> <th>Device Id</th><th>Current State Json</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $device_state->id }}</td> <td> {{ $device_state->device_id }} </td><td> {{ $device_state->current_state_json }} </td>
                </tr>
            </tbody>    
        </table>
    </div>

@endsection
