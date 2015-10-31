@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
    @include('scripts.datatables')
    <script>
        var oTable;
        var $table = $('#table_users');
        $(document).ready(function() {
            oTable = $table.DataTable({
                "sDom": getTableTemplate(),
                "bPaginate": true,
                "processing": false,
                "order": [[ 2, "desc" ]],
                "ajax": {
                    url : "/app-users/data/?app=" + getUrlParam('app')
                },
                "columns": [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'email'},
                    {data: 'phone'},
                    {data: 'last_status'},
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
            <div class="row">
                <div class="col-md-1 manage-btn">
                    <a href="{{{ URL::to('app-users/create/?app='.$app->id) }}}"
                       data-target="#myModal"
                       data-toggle="modal"
                       class="btn btn-labeled btn-info">
                        <span class="btn-label">
                               <i class="fa fa-plus"></i>
                           </span>Create User
                    </a>
                </div>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table_users" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>E-mail</th>
                            <th>Phone</th>
                            <th>Last status</th>
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
