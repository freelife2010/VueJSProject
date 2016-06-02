@extends('layouts.auth')

@section('content')
    <div class="panel-body">
        <p class="text-center pv">SIGN IN TO CONTINUE</p>
        @if (count($errors) > 0)
            <div class="alert alert-danger">
                <strong>{{ Lang::get('auth.whoops') }}</strong>{{ Lang::get('auth.someProblems') }}<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form role="form" data-parsley-validate=""
              class="mb-lg"
              method="POST"
              action="{{ url('/auth/login') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
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
            <div class="clearfix">
                <div class="checkbox c-checkbox pull-left mt0">
                    <label>
                        <input type="checkbox" name="remember" value="" name="remember">
                        <span class="fa fa-check"></span>Remember Me</label>
                </div>
                <div class="pull-right"><a href="{{ url('/password/email') }}" class="text-muted">Forgot your password?</a>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-block btn-primary mt-lg">Login</button>
            </div>
        </form>

        <p class="pt-lg text-center">Need to Signup?</p><a href="{{ url('/auth/register') }}" class="btn btn-block btn-default">Register Now</a>
    </div>
@endsection
