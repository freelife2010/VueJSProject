@extends('app')
@section('wrapper_content')
    <div class="block-center mt-xl wd-xl">
        <div class="panel panel-dark panel-flat">
            <div class="panel-heading text-center">
                <a href="/">
                    <img src="/img/logo.png" alt="Image" class="block-center img-rounded">
                </a>
            </div>
            @yield('content')
        </div>
    </div>
@endsection
