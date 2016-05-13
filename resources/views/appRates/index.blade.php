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
                "serverSide": true,
                "processing": true,
                "order": [[ 3, "desc" ]],
                "ajax": {
                    url : '{{ url("/app-rates/data?app=".$APP->id) }}'
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Export to CSV',
                        className: 'btn btn-primary',
                        action: function ( e, dt, node, config ) {
                            window.location.href='{{ url("/app-rates/data?app=".$APP->id) }}';
                        }
                    }
                ],
                "columns": [
                    {data: 'country', name: 'country'},
                    {data: 'destination', name: 'destination'},
                    {data: 'code', name: 'code'},
                    {data: 'rate', name: 'rate'},
                    {data: 'custom_rate', name: 'custom_rate'}
                ],
                "fnDrawCallback": function() {
                    setAppRateBtnEvent();
                }
            });
        });

        function setAppRateBtnEvent() {
            $('.add_rate_btn').click(function(e) {
                e.preventDefault();
                var $this= $(this);
                var url = $this.prop('href');
                var rateInput = $this.prev().find('input');
                if (!rateInput.val())
                    rateInput.parent().addClass('has-error');
                else setAppRate(url, $this, rateInput);
            });
        }

        function setAppRate(url, addRateBtn, rateInput) {
            var rate = rateInput.val();
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    rate: rate,
                    _token: $('.content-wrapper').find('input[name=_token]').val()
                },
                beforeSend: function() {
                    rateInput.prop('disabled', true);
                    addRateBtn.prop('disabled', true);
                },
                success: function(data) {
                    var type = data.error == 0 ? 'success' : 'danger';
                    showMessage(type, data.alert);
                    if (type == 'success')
                        reloadTables();
                },
                error: function(data) {
                    showDefaultErrorMessage(data);
                },
                complete: function() {
                    rateInput.prop('disabled', false);
                    addRateBtn.prop('disabled', false);
                }
            });
        }


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
                            <th>Country</th>
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
    <?= csrf_field() ?>
@endsection
