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
                    url : "/costs/did-data"
                },
                "columns": [
                    {data: 'country_id'},
                    {data: 'state'},
                    {data: 'rate_center'},
                    {data: 'value'},
                    {data: 'actions'}
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
                <a href="/costs/did-create"
                   data-target="#myModal"
                   data-toggle="modal"
                   class="btn btn-labeled btn-info">
                    <span class="btn-label">
                           <i class="fa fa-plus"></i>
                       </span>Set new cost
                </a>
                <a href="/costs/did-default"
                   data-target="#myModal"
                   data-toggle="modal"
                   class="btn btn-labeled {{ $defaultButtonOptions['type'] }}">
                    <span class="btn-label">
                           <i class="fa fa-tag"></i>
                       </span>{{ $defaultButtonOptions['label'] }}
                </a>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>Country</th>
                            <th>State</th>
                            <th>Rate Center</th>
                            <th>Value (USD)</th>
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
