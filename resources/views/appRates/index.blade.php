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
                "order": [[ 3, "desc" ]],
                "ajax": {
                    url : '{{ url("/app-rates/data?app=".$APP->id) }}'
                },
                "columns": [
                    {data: 'country'},
                    {data: 'code_name'},
                    {data: 'rate'},
                    {data: 'custom_rate'}
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
                            <th>Destination</th>
                            <th>Code</th>
                            <th>Opentact Sell Rate</th>
                            <th>Your Sell Rate</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection