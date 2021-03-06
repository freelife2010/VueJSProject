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
                    url : "/conferences/log-data/?app=" + getUrlParam('app')
                },
                "columns": [
                    {data: 'conference_id'},
                    {data: 'name'},
                    {data: 'enter_time'},
                    {data: 'leave_time'},
                    {data: 'caller_id'},
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
                            <th>Conference ID</th>
                            <th>Conference Name</th>
                            <th>Enter Time</th>
                            <th>Leave Time</th>
                            <th>Caller ID</th>
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
