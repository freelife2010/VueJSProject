@extends('layouts.default')
@include('partials.is_active', ['path' => 'emails'])
@section('title')
    {{ $title }} :: @parent
@endsection
@section('subtitle') Modify authorization e-mail content @stop
@section('content')
@endsection