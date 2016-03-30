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
                "ajax": {
                    url: "{{ URL::to('cdr/data/') }}",
                    type: "GET",
                    data : function (d) {
                        d.call_type = $('#call_type').val();
                    }
                },
                "columns": [
                    {data: 'time', name: 'time'},
                    {data: 'trunk_id_origination', name: 'trunk_id_origination'},
                    {data: 'alias', name: 'resource.alias'},
                    {data: 'origination_source_number', name: 'origination_source_number'},
                    {data: 'origination_destination_number', name: 'origination_destination_number'},
                    {data: 'call_duration', name: 'call_duration'},
                    {data: 'agent_rate', name: 'agent_rate'},
                    {data: 'agent_cost', name: 'agent_cost'}
                ],
                "fnDrawCallback": function() {
                    $('.col-filter').css('width', '16%');
                    setCallTypeEvents();
                }
            });
        });

        function setCallTypeEvents()
        {
            $('#call_type').change(function() {
                oTable.ajax.reload();
            });
        }


    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <br/>
            <div style="width: 30%">
                <?= Former::horizontal_open() ?>
                <?= Former::select('call_type')->options($callTypes, 0)->label('Call type')
                        ->style('width: 150px')?>
                <?= Former::close()?>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover cursor-pointer">
                        <thead>
                        <tr>
                            <th>Time</th>
                            <th>APP</th>
                            <th>User</th>
                            <th>Call From</th>
                            <th>Call To</th>
                            <th>Duration</th>
                            <th>Rate</th>
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
