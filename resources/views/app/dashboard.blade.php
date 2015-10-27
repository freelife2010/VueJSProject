@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
    @include('scripts.datatables')
    <script>
        var oTable;
        var $table = $('#table');
        $(document).ready(function() {
            oTable = $table.DataTable({
                "sDom": getTableTemplate(),
                "bPaginate": true,
                "processing": false,
                "order": [[ 2, "desc" ]],
                "ajax": {
                    url : "app/data"
                },
                "columns": [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'users'},
                    {data: 'daily_active'},
                    {data: 'weekly_active'},
                    {data: 'monthly_active'},
                    {data: 'presence'},
                    {data: 'actions'}
                ],
                "fnDrawCallback": function() {
                    $('.col-filter').css('width', '16%');
                }
            });
        });
    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="pull-right" id="create-btn">
                        <div class="pull-right">
                            <a href="{{{ URL::to('app/create') }}}"
                               data-target="#myModal"
                               data-toggle="modal"
                               class="btn btn-primary">
                                <em class="fa fa-plus-circle"></em> Create APP</a>
                        </div>
                    </div>
                    <table id="table" class="table table-striped table-hover cursor-pointer">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Users</th>
                            <th>Daily active</th>
                            <th>Weekly active</th>
                            <th>Monthly active</th>
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
