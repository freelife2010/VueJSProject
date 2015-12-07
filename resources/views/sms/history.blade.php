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
                    url : "/sms/data/"
                },
                "columns": [
                    {data: 'id'},
                    {data: 'src'},
                    {data: 'dst'},
                    {data: 'msg'},
                    {data: 'dt'},
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
                    <table id="table" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Message</th>
                            <th>Datetime</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
