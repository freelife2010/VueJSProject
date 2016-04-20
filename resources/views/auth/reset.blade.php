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

        <form class="mb-lg" role="form"
              data-parsley-validate=""
              method="POST" action="{{ url('/password/reset') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group has-feedback">
                <input type="email" placeholder="Enter email"
                       name="email"
                       required class="form-control"
                       value="{{ old('email') }}">
                <span class="fa fa-envelope form-control-feedback text-muted"></span>
            </div>

            <div class="form-group has-feedback">
                <input type="password" name="password" placeholder="Password" required class="form-control">
                <span class="fa fa-lock form-control-feedback text-muted"></span>
            </div>

            <div class="form-group has-feedback">
                <input type="password" name="password_confirmation"
                       data-parsley-equalto='#password'
                       placeholder="{{ Lang::get('auth.confirmPassword') }}" required class="form-control">
                <span class="fa fa-lock form-control-feedback text-muted"></span>
            </div>

            <div class="form-group">
                    <button type="submit" class="btn btn-block btn-primary mt-lg">
                        {{ Lang::get('auth.resetPassword') }}
                    </button>
            </div>
        </form>
    </div>
@endsection
