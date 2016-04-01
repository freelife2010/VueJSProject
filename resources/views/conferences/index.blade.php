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
                "order": [[ 3, "asc" ]],
                "ajax": {
                    url : '{{ url("/conferences/data?app=".$APP->id) }}'
                },
                "columns": [
                    {data: 'id'},
                    {data: 'name'},
                    {data: 'host_pin'},
                    {data: 'guest_pin'},
                    {data: 'greeting_prompt'},
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
                <div class="col-md-1 manage-btn">
                    <a href="{{{ URL::to('conferences/create?app='.$APP->id) }}}"
                       data-target="#myModal"
                       data-toggle="modal"
                       class="btn btn-labeled btn-info">
                        <span class="btn-label">
                               <i class="fa fa-plus"></i>
                           </span>Create conference
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
                            <th>Name</th>
                            <th>Host PIN</th>
                            <th>Guest PIN</th>
                            <th>Greeting Prompt</th>
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
