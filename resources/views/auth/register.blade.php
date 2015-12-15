@extends('layouts.auth')

@section('content')
    <div class="panel-body">
        @if (count($errors) > 0)
            <div class="alert alert-danger">
                <strong>{{ Lang::get('auth.whoops') }}</strong> {{ Lang::get('auth.someProblems') }}<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <p class="text-center pv">SIGNUP TO GET INSTANT ACCESS.</p>
        <form role="form" data-parsley-validate="" class="mb-lg"
              method="POST" action="{{ url('/auth/register') }}">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <div class="form-group has-feedback">
                <label for="name" class="text-muted">Name</label>
                <input type="text"
                       name="name" id="name"
                       value="{{ old('name') }}"
                       placeholder="Name" autocomplete="off" required class="form-control">
                <span class="fa fa-user form-control-feedback text-muted"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="email" class="text-muted">Email address</label>
                <input type="email" placeholder="Enter email"
                       name="email" id="email"
                       value="{{ old('email') }}"
                       autocomplete="off" required class="form-control">
                <span class="fa fa-envelope form-control-feedback text-muted"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="password" class="text-muted">Password</label>
                <input type="password"
                       name="password" id="password"
                       placeholder="Password" autocomplete="off" required class="form-control">
                <span class="fa fa-lock form-control-feedback text-muted"></span>
            </div>
            <div class="form-group has-feedback">
                <label for="signupInputRePassword1" class="text-muted">Retype Password</label>
                <input id="signupInputRePassword1" type="password" placeholder="Retype Password" autocomplete="off"
                       name="password_confirmation"
                       required data-parsley-equalto="#password" class="form-control">
                <span class="fa fa-lock form-control-feedback text-muted"></span>
            </div>
            <div class="clearfix">
                <div class="checkbox c-checkbox pull-left mt0">
                    <label>
                        <input type="checkbox" value="" required name="agreed">
                        <span class="fa fa-check"></span>I agree with the <a href="#">terms</a>
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-block btn-primary mt-lg">Create account</button>
        </form>
        <p class="pt-lg text-center">Have an account?</p><a href="{{ url('/auth/login') }}"
                                                            class="btn btn-block btn-default">Sign in</a>
    </div>
@endsection
