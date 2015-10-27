@extends('app')
@section('wrapper_content')
    <div class="abs-center wd-xl">
        <!-- START panel-->
        <div class="text-center mb-xl">
            <div class="text-lg mb-lg">404</div>
            <p class="lead m0">We couldn't find this page.</p>
            <p>The page you are looking for does not exists.</p>
        </div>
        <ul class="list-inline text-center text-sm mb-xl">
            <li><a href="{{ url('') }}" class="text-muted">Go to App</a>
            </li>
            <li class="text-muted">|</li>
            <li><a href="{{ url('auth/login') }}" class="text-muted">Login</a>
            </li>
            <li class="text-muted">|</li>
            <li><a href="{{ url('auth/register') }}" class="text-muted">Register</a>
            </li>
        </ul>
        <div class="p-lg text-center">
            <span>&copy;</span>
            <span>2015</span>
            <span>-</span>
            <span>AdminUI</span>
            <br>
        </div>
    </div>
@endsection