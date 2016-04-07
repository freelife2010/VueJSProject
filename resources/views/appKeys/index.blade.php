@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
    @include('scripts.datatables')
    <script src="{{ asset('vendor/chosen_v1.2.0/chosen.jquery.min.js') }}"></script>
    <script>
        var oTable;
        var $table = $('#table');
        $(document).ready(function() {
            oTable = $table.DataTable({
                "bPaginate": true,
                "processing": false,
                "order": [[ 3, "desc" ]],
                "ajax": {
                    url : '{{ url("/app-keys/data?app=".$APP->id) }}'
                },
                "columns": [
                    {data: 'app_id'},
                    {data: 'id'},
                    {data: 'secret'},
                    {data: 'scopes'},
                    {data: 'created_at'},
                    {data: 'days_left'},
                    {data: 'status'},
                    {data: 'actions'}
                ],
                "fnDrawCallback": function() {
                }
            });
        });


    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-1 manage-btn">
                    <a href="{{{ URL::to('app-keys/create?app='.$APP->id) }}}"
                       data-target="#myModal"
                       data-toggle="modal"
                       class="btn btn-labeled btn-info">
                        <span class="btn-label">
                               <i class="fa fa-key"></i>
                           </span>Create API keys
                    </a>
                </div>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover ">
                        <thead>
                        <tr>
                            <th>APP Name</th>
                            <th>APP UUID</th>
                            <th>Secret</th>
                            <th>Permitted APIs</th>
                            <th>Created at</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
