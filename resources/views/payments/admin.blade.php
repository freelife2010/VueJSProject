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
                "order": [[ 0, "desc" ]],
                "ajax": "{{ URL::to('payments/admin-data/') }}",
                "columns": [
                    {data: 'account_id', name: 'account_id'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'amount', name: 'amount'},
                    {data: 'type', name: 'type'},
                    {data: 'transaction_id', name: 'transaction_id'}
                ]
            });
        });

    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-md-5">
            @include('flash::message')
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>Developer</th>
                            <th>Time</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Transaction ID</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
