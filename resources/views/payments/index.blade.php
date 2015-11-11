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
                "processing": true,
                "serverSide": true,
                "order": [[ 2, "desc" ]],
                "ajax": "{{ URL::to('payments/data/') }}",
                "columns": [
                    {data: 'client_id', name: 'client_id'},
                    {data: 'invoice_id', name: 'invoice_id'},
                    {data: 'country', name: 'country'},
                    {data: 'city', name: 'city'},
                    {data: 'address1', name: 'address1'},
                    {data: 'chargetotal', name: 'chargetotal'},
                    {data: 'confirmed', name: 'confirmed'},
                    {data: 'created_time', name: 'created_time'}
                ]
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
                            <th>Client ID</th>
                            <th>Invoice ID</th>
                            <th>Country</th>
                            <th>City</th>
                            <th>Address</th>
                            <th>Charged total</th>
                            <th>Confirmed</th>
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
