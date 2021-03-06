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
               if (appUser.val())
                initTable(appUser.val());
            });
        });

        function initTable(appUserId) {
            var $table = $('#table');
            if (!oTable)
                oTable = $table.DataTable({
                    "bPaginate": true,
                    "processing": true,
                    "order": [[ 0, "desc" ]],
                    "ajax": {
                        url : "/app-users/sip-accounts-data/?app=" + getUrlParam('app'),
                        data: {
                            app_user_id: appUserId
                        }
                    },
                    "columns": [
                        {data: 'resource_ip_id'},
                        {data: 'username'},
                        {data: 'password'},
                        {data: 'reg_status'},
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
                <div class="col-md-2">
                    <a href="{{{ URL::to('app-users/create-sip-account/?app='.$APP->id) }}}"
                       data-target="#myModal"
                       data-toggle="modal"
                       class="btn btn-labeled btn-info">
                        <span class="btn-label">
                               <i class="fa fa-plus"></i>
                           </span>Create SIP Account
                    </a>
                </div>
            </div>
            <div style="width: 30%">

            </div>
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Caller ID</th>
                            <th>Password</th>
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
