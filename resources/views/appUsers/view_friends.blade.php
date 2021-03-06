@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@include('styles.datatables')
@section('scripts')
    @include('scripts.datatables')
    <script>
        var oTable = null;
        $(document).ready(function() {
            var appUser = $('#app_user');
            appUser.change(function() {
//                if (appUser.val())
                initTable($(this).val());
            });
            appUser.change();
        });

        function initTable(appUserId) {
            appUserId = appUserId || 0;

            var $table = $('#table_users');
            if (!oTable)
                oTable = $table.DataTable({
                    "bPaginate": true,
                    "processing": false,
                    "order": [[ 2, "desc" ]],
                    "ajax": {
                        url : "/app-users/view-friend-request-data",
                        data: {
                            app: getUrlParam('app'),
                            appUserId: appUserId
                        }
                    },
                    "columns": [
                        {data: 'id'},
                        {data: 'name'},
                        {data: 'email'},
                        {data: 'phone'},
                        {data: 'did'},
                        {data: 'balance'},
                        {data: 'last_status'},
                        {data: 'actions'}
                    ],
                    "fnDrawCallback": function() {
                        $('.col-filter').css('width', '16%');
                    }
                });
            else {
                oTable.destroy();
                oTable = null;
                initTable(appUserId);
            }
        }

    </script>
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="row">
                <div class="col-md-3">
                <?= Former::horizontal_open() ?>
                <!--email_off-->
                <?= Former::select('app_user')
                        ->addOption('Select APP user')
                        ->options($appUsers, 0)
                        ->label('')->raw()?>
                <?= Former::close()?>
                <!--/email_off-->
                </div>
            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table_users" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>E-mail</th>
                            <th>Phone</th>
                            <th>DID</th>
                            <th>Balance</th>
                            <th>Status</th>
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