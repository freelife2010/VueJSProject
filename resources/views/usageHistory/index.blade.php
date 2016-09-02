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
                    url : "/usage-history/data"
                },
                "columns": [
                    {data: 'id'},
                    {data: 'trunk'},
                    {data: 'minutes'},
                    {data: 'cost'}
//                    {data: 'daily_active'},
//                    {data: 'weekly_active'},
//                    {data: 'monthly_active'},
//                    {data: 'presence'},
//                    {data: 'actions'}
                ],
                "fnDrawCallback": function() {
//                    bindRowEvents();
                }
            });
        });

        function bindRowEvents() {
            var $tr = $('#table').find('tr:not(:first)');
            $.each($tr, function (key, val) {
                var $this = $(val);
                var td = $this.find('td:not(:last)');
                var id = $this.prop('id');
                td.click(function(e) {
                    openAppDashboard(id);
                });
            });
        }

        function openAppDashboard(id) {
            window.location.href = '/app/dashboard/?app='+id;
        }
    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-1 manage-btn">
                </div>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover cursor-pointer">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trunk</th>
                            <th>Minutes</th>
                            <th>Cost</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
