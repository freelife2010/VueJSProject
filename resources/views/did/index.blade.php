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
                "order": [[ 0, "desc" ]],
                "ajax": {
                    url : '{{ url("did/data?app=".$APP->id) }}'
                },
                "columns": [
                    {data: 'id'},
                    {data: 'did'},
                    {data: 'owned_by'},
                    {data: 'state'},
                    {data: 'rate_center'},
                    {data: 'name'},
                    {data: 'created_at'},
                    {data: 'actions'}
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
            <div class="row">
                <div class="col-md-1 manage-btn">
                    <a href="{{{ URL::to('did/create?app='.$APP->id) }}}"
                       data-target="#myModal"
                       data-toggle="modal"
                       class="btn btn-labeled btn-info">
                        <span class="btn-label">
                               <i class="fa fa-phone"></i>
                           </span>Buy DID
                    </a>
                </div>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover ">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>DID</th>
                            <th>APP User</th>
                            <th>State</th>
                            <th>Rate Center</th>
                            <th>DID Action</th>
                            <th>Created at</th>
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
