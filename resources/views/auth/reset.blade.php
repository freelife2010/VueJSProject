@extends('layouts.auth')
@section('styles')
    <style>
        .control-label {
            padding-top: 0 !important;
        }
    </style>
@endsection
@section('content')
    <div class="panel-body">
        <p class="text-center pv">PASSWORD RESET</p>
        @if (count($errors) > 0)
            <div class="alert alert-danger">
                {{ Lang::get('auth.someProblems') }}<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form-horizontal" role="form"
              data-parsley-validate=""
              method="POST" action="{{ url('/password/reset') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label class="col-md-4 control-label">{{ Lang::get('auth.email') }}</label>
                <div class="col-md-6">
                    <input type="email" class="form-control" name="email" required value="{{ old('email') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-4 control-label">{{ Lang::get('auth.password') }}</label>
                <div class="col-md-6">
                    <input type="password" class="form-control" required id="password"
                           name="password">
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-4 control-label">{{ Lang::get('auth.confirmPassword') }}</label>
                <div class="col-md-6">
                    <input type="password" class="form-control" data-parsley-equalto='#password'
                           required
                           name="password_confirmation">
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-6 col-md-offset-4">
                    <button type="submit" class="btn btn-primary">
                        {{ Lang::get('auth.resetPassword') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
