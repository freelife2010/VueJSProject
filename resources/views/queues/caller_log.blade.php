@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
    @include('scripts.datatables')
    <script>
        var oTable;
        var $table = $('#table_log');
        $(document).ready(function() {
            oTable = $table.DataTable({
                "bPaginate": true,
                "processing": false,
                "order": [[ 2, "desc" ]],
                "ajax": {
                    url : "/queues/caller-data/?app=" + getUrlParam('app')
                },
                "columns": [
                    {data: 'queue_id'},
                    {data: 'queue_name'},
                    {data: 'join_time'},
                    {data: 'leave_time'},
                    {data: 'uuid'}
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
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table_log" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>Queue ID</th>
                            <th>Queue name</th>
                            <th>Enter time</th>
                            <th>Leave Time</th>
                            <th>UUID</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
