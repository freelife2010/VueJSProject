@extends('app')
@section('wrapper_content')
    @include('partials.header')
    @role('admin')
        @include('partials.admin_sidebar', ['user' => Auth::user(),
                                            'helper' => $helper])
    @endrole
    @role('developer')
        @include('partials.developer_sidebar' , ['user' => Auth::user(),
                                                 'helper' => $helper])
    @endrole
    @include('partials.offsidebar')
    <section>
        <div class="content-wrapper">
            <div class="content-heading">
                {!! $title !!}
                <small>@yield('subtitle')</small>
            </div>
            @include('partials.notifications')
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>
    </section>
@endsection
