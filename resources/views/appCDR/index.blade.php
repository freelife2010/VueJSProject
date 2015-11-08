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
                    url : "{{ URL::to('app-cdr/data/?app='.$APP->id) }}",
                    type: "GET",
                    data : function (d) {
                        d.call_type = $('#call_type').val();
                    }
                },
                "columns": [
                    {data: 'session_id', name: 'session_id'},
                    {data: 'start_time_of_date', name: 'start_time_of_date'},
                    {data: 'release_tod', name: 'release_tod'},
                    {data: 'ani_code_id', name: 'ani_code_id'},
                    {data: 'dnis_code_id', name: 'dnis_code_id'},
                    {data: 'call_duration', name: 'call_duration'},
                    {data: 'agent_rate', name: 'agent_rate'},
                    {data: 'agent_cost', name: 'agent_cost'},
                    {data: 'origination_source_number', name: 'origination_source_number'},
                    {data: 'origination_destination_number', name: 'origination_destination_number'}
                ],
                "fnDrawCallback": function() {
                    $('.col-filter').css('width', '16%');
                    bindRowEvents();
                    setCallTypeEvents();
                }
            });
        });

        function bindRowEvents() {
            var $tr = $('#table').find('tr:not(:first)');
            $.each($tr, function (key, val) {
                var $this = $(val);
                var td = $this.find('td:not(:last)');
                var id = $this.find('td:first').text();
                td.click(function(e) {
//                    openAppDashboard(id);
                });
            });
        }

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
                <?= Former::select('call_type')->options($callTypes, 1)->label('Call type')
                        ->style('width: 150px')?>
                <?= Former::close()?>
            </div>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover cursor-pointer">
                        <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Start time</th>
                            <th>End time</th>
                            <th>ANI</th>
                            <th>DNIS</th>
                            <th>Duration</th>
                            <th>Rate</th>
                            <th>Cost</th>
                            <th>Source Number</th>
                            <th>Destination Number</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
