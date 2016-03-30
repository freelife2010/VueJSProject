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
                "bPaginate": true,
                "processing": false,
                "order": [[ 2, "desc" ]],
                "ajax": {
                    url : "{{url('/users/apps/'.$model->id)}}"
                },
                "columns": [
                    {data: 'tech_prefix'},
                    {data: 'name'},
                    {data: 'users'},
                    {data: 'daily_active'},
                    {data: 'weekly_active'},
                    {data: 'monthly_active'},
                    {data: 'presence'},
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
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover ">
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
