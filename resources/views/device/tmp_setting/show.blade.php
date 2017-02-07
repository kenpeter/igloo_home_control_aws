@extends('layouts.master')

@section('content')

    <h1>Tmp setting</h1>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead>
                <tr>
                    <th>ID.</th> <th>Name</th><th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $tmp_setting->id }}</td> <td> {{ $tmp_setting->name }} </td><td> {{ $tmp_setting->value }} </td>
                </tr>
            </tbody>    
        </table>
    </div>

@endsection
