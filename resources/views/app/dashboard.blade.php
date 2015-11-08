@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
        });
    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Manage APP</div>
                <div class="panel-body">
                    <div class="col-md-2 manage-btn">
                        <a href="{{{ URL::to('app-keys/index/?app='.$APP->id) }}}"
                           class="btn btn-labeled btn-xl btn-info">
                            <span class="btn-label">
                                   <i class="fa fa-key"></i>
                               </span> APP API keys
                        </a>
                    </div>
                    <div class="col-md-2 manage-btn">
                        <a href="{{{ URL::to('app-users/index/?app='.$APP->id) }}}"
                           class="btn btn-labeled btn-xl btn-info">
                            <span class="btn-label">
                                   <i class="fa fa-user"></i>
                               </span> APP Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
