<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="description" content="AdminUI">
    <meta name="keywords" content="adminui, billing">
    <title>@section('title') AdminUI @show</title>
    <!-- =============== VENDOR STYLES ===============-->
    <!-- FONT AWESOME-->
    <link rel="stylesheet" href="{{ asset('bower_components/fontawesome/css/font-awesome.min.css')}}">
    <!-- SIMPLE LINE ICONS-->
    <link rel="stylesheet" href="{{ asset('bower_components/simple-line-icons/css/simple-line-icons.css')}}">
    <!-- ANIMATE.CSS-->
    <link rel="stylesheet" href="{{ asset("bower_components/animate.css/animate.min.css") }}">
    <!-- WHIRL (spinners)-->
    <link rel="stylesheet" href="{{ asset("bower_components/whirl/dist/whirl.css") }}">
    <!-- =============== PAGE VENDOR STYLES ===============-->
    <!-- WEATHER ICONS-->
    <link rel="stylesheet" href="{{ asset("bower_components/weather-icons/css/weather-icons.min.css") }}">
    <!-- =============== BOOTSTRAP STYLES ===============-->
    <link rel="stylesheet" href="{{ asset("css/bootstrap.css") }}" id="bscss">
    <!-- =============== APP STYLES ===============-->
    <link rel="stylesheet" href=" {{ asset("css/app.css") }}" id="maincss">

    @yield('styles')
</head>
<body class="aside-hover">
    <div class="wrapper">
        @yield('wrapper_content')
    </div>

    <!-- =============== VENDOR SCRIPTS ===============-->
    <!-- MODERNIZR-->
    <script src="{{ asset('bower_components/modernizr/modernizr.js') }}"></script>
    <!-- JQUERY-->
    <script src="{{ asset('bower_components/jquery/dist/jquery.js') }}"></script>
    <!-- BOOTSTRAP-->
    <script src="{{ asset('bower_components/bootstrap/dist/js/bootstrap.js') }}"></script>
    <!-- STORAGE API-->
    <script src="{{ asset('bower_components/jQuery-Storage-API/jquery.storageapi.js') }}"></script>
    <!-- PARSLEY-->
    <script src="{{ asset('bower_components/parsleyjs/dist/parsley.min.js') }}"></script>
    <script src="{{ asset('bower_components/jquery-classyloader/js/jquery.classyloader.min.js') }}"></script>
    <!-- MOMENT JS-->
    <script src="{{ asset('bower_components/moment/min/moment-with-locales.min.js') }}"></script>
    <!-- DEMO-->
    <script src="{{ asset('js/demo/demo-flot.js') }}"></script>

    @yield('scripts')

    <!-- =============== APP SCRIPTS ===============-->
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/submit_events.js') }}"></script>
</body>
</html>
