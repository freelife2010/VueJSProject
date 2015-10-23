@extends('app')
@section('wrapper_content')
    @include('partials.header')
    @role('admin')
        @include('partials.sidebar')
    @endrole
    @role('developer')
        @include('partials.sidebar')
    @endrole
    @include('partials.offsidebar')
    <section>
        <div class="content-wrapper">
            <div class="content-heading">
                @yield('title')
                <small>@yield('subtitle')</small>
            </div>
            @yield('content')
        </div>
    </section>
@endsection
