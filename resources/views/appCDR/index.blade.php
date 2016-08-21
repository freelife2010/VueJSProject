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
                        d.filter = $('#filter').val();
                        d.call_type = $('#call_type').val();
                    }
                },
                "columns": [
                    {data: 'time', name: 'time'},
                    {data: 'trunk_id_origination', name: 'trunk_id_origination'},
                    {data: 'alias', name: 'resource.alias'},
                    {data: 'origination_source_number', name: 'origination_source_number'},
                    {data: 'routing_digits', name: 'routing_digits'},
                    {data: 'call_duration', name: 'call_duration'},
                    {data: 'ingress_client_rate', name: 'ingress_client_rate'},
                    {data: 'ingress_client_cost', name: 'ingress_client_cost'}
                ],
                "fnDrawCallback": function() {
                    $('.col-filter').css('width', '16%');
                    setCallTypeEvents();
                }
            });
        });

        $(document).on('change', '#filter', function() {
            oTable.ajax.reload();
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
                <div class="form-group">
                    <?= Former::horizontal_open() ?>
                    <div class="row">
                        <?= Former::select('filter')->options($filterTypes, 0)->label('Filter')->style('width: 150px')?>

                        <label for="call_type" class="col-sm-3">Call type</label>
                        <?= Former::select('call_type')->options($callTypes, 0)
                                ->style('width: 150px')->raw()?>
                    </div>
                </div>
                <?= Former::close()?>
            </div>
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
