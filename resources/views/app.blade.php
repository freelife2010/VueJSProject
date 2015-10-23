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
<body>
    <div class="wrapper">
        @yield('wrapper_content')
    </div>

    @yield('scripts')
	<!-- Scripts -->
	<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script>
</body>
</html>
