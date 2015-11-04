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
                    <div class="col-md-1 manage-btn">
                        <a href="{{{ URL::to('app/generate-keys/?app='.$APP->id) }}}"
                           data-target="#myModal"
                           data-toggle="modal"
                           class="btn btn-labeled btn-xl
                            {{($APP->key and $APP->key->isExpired()) ? 'btn-warning' : 'btn-info'}}">
                            <span class="btn-label">
                                   <i class="fa fa-key"></i>
                               </span> {{ $APP->key ? 'Regenerate' : 'Generate' }} API keys
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
