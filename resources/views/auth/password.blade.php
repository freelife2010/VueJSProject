@extends('layouts.auth')

@section('content')
    <div class="panel-body">
        <p class="text-center pv">PASSWORD RESET</p>
        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

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
        <form role="form" method="POST" data-parsley-validate="">
            <input type="hidden" name="_token" value="{{ csrf_token() }}" >
            <p class="text-center">Enter your mail to receive instructions on how to reset your password.</p>
            <div class="form-group has-feedback">
                <label for="resetInputEmail1" class="text-muted">Email address</label>
                <input id="resetInputEmail1" type="email" placeholder="Enter email"
                       name="email"
                       required
                       value="{{ old('email') }}"
                       class="form-control">
                <span class="fa fa-envelope form-control-feedback text-muted"></span>
            </div>
            <button type="submit" class="btn btn-danger btn-block">Reset</button>
        </form>
    </div>
@endsection
