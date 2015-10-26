@extends('layouts.default')
@section('title')
    Home :: @parent
@endsection
@section('heading') Page heading @stop
@section('content')
<div class="row">
    <div class="col-md-10">
        <div class="panel panel-default">
            <div class="panel-heading">{{ Lang::get('titles.home') }}</div>

            <div class="panel-body">
                {{ Lang::get('auth.loggedIn') }}
            </div>
        </div>
    </div>
</div>
@endsection
