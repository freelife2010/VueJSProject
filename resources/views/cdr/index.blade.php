@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
   @include('scripts.datatables')
   <script src="{{ asset('bower_components/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
   <script src="{{ asset('bower_components/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
   <script src="{{ asset('bower_components/datatables.net-buttons/js/buttons.flash.min.js') }}"></script>
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
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Export to CSV',
                        className: 'btn btn-primary',
                        action: function ( e, dt, node, config ) {
                            var callType = $('#call_type').val();
                            window.location.href='/cdr/csv?call_type=' + callType;
                        }
                    }
                ],
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

            $("#table_filter").append('<div class="row col-md-6"><div class="col-md-5"><div class="form-group"><div class="input-group date" id="datetimepicker6"><input type="text" id="from_date" class="form-control" />' +
            '<span class="input-group-addon"><span class="fa fa-calendar"></span></span></div></div></div><div class="col-md-5"><div class="form-group"><div class="input-group date" id="datetimepicker7">' +
                    '<input type="text" id="to_date" class="form-control" /><span class="input-group-addon"><span class="fa fa-calendar"></span></span></div></div></div><div class="ol-md-2">' +
                    '<button type="button" id="search_date" class="dt-button btn btn-primary">Search</button></div></div>'
            );
        });
        $(document).on("click","#search_date",function() {
            var from_date = $("#from_date").val();
            var to_date = $("#to_date").val();
            var dataString = 'from_date='+from_date;
            var CSRF_TOKEN = '<?=csrf_token()?>';
            if(from_date != ''){
                $.ajax({
                    type: "get",
                    url: 'cdr/data',
                    data: {'from_date':from_date,'to_date':to_date,_token: CSRF_TOKEN},
                    success: function(data){
//                        $('input[type=search]').keyup();
                    }
                });
            }
        });

        function setCallTypeEvents()
        {
            $('#call_type').change(function() {
                oTable.ajax.reload();
            });
        }
    </script>

   <script type="text/javascript">

       $(function () {
           $('#datetimepicker6').datetimepicker({
               format: 'YYYY-MM-DD',
           });
           $('#datetimepicker7').datetimepicker({
               format: 'YYYY-MM-DD',
               useCurrent: false //Important! See issue #1075
           });

           $("#datetimepicker6").on("dp.change", function (e) {
               $('#datetimepicker7').data("DateTimePicker").minDate(e.date);
           });
           $("#datetimepicker7").on("dp.change", function (e) {
               $('#datetimepicker6').data("DateTimePicker").maxDate(e.date);
           });
       });


   </script>

   <link rel="stylesheet" href="http://portal.opentact.org/vendor/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css">
   <script src="http://portal.opentact.org/vendor/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <br/>
            <div style="width: 40%">
                <?= Former::horizontal_open() ?>
                <?= Former::select('filter')->options(['Peer to Peer', 'DID Calls', 'Toll Free Calls', 'Forwarded Callse', 'Diales Calls'], 0)->label('Filter')
                        ->style('width: 150px')?>
                <?= Former::close()?>
            </div>
            <div style="width: 40%">
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
