@extends('layouts.auth')

@section('content')
    <div class="panel-body">
        <p>{{ Lang::get('auth.sentEmail',
            ['email' => $email] ) }}</p>

        <p>Please click the link in it to activate your account.</p>
    </div>
@endsection




