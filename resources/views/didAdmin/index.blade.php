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
//            oTable = $table.DataTable({
//                "bPaginate": true,
//                "processing": false,
//                "order": [[ 0, "desc" ]],
//                "ajax": {
                    {{--url : '{{ url("url") }}'--}}
//                },
//                "columns": [
//                    {data: 'id'},
//                    {data: 'did'},
//                    {data: 'created_at'},
//                    {data: 'developer'},
//                    {data: 'app'},
//                    {data: 'owned_by'},
//                    {data: 'name'}
//                ],
//                "fnDrawCallback": function() {
//                }
//            });
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
                            <th>Code</th>
                            <th>Country</th>
                            <th>Area</th>
                            <th>Rate</th>
                            <th>Vendor</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
