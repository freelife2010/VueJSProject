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
                    url : "/revisions/data"
                },
                "columns": [
                    {data: 'id'},
                    {data: 'revisionable_type'},
                    {data: 'revisionable_id'},
                    {data: 'user_id'},
                    {data: 'key'},
                    {data: 'old_value'},
                    {data: 'new_value'},
                    {data: 'created_at'}
                ]
            });
        });


        function openAppDashboard(id) {
            window.location.href = '/app/keys/?app='+id;
        }
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
                    <table id="table" class="table table-striped table-hover">
                        <thead>
                        <tr class="th-revision">
                            <th>ID</th>
                            <th>Entity</th>
                            <th>Entity ID</th>
                            <th>Modified by</th>
                            <th>Field</th>
                            <th>Old value</th>
                            <th>New value</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
