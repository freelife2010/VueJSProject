@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            $('#app_user_id').val($('#app_user').val());
        });
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("app-users/create-sip-account?app=$APP->id");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("app-users/edit-sip-account/$model->id?app=$APP->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::hidden('id');?>
        <?= Former::select('app_user_id')
                ->addOption('Select APP user')
                ->options($appUsers, 0)
                ->label('App User')?>
        <?= Former::text('password')->type('password')->label('Password');?>
    </div>
    <div style="clear: both"></div>
    <br/>
    <div class="pull-right">
        <?= Former::actions(
                Former::primary_button($submit_label)
                        ->type('submit')->setAttribute('data-submit', 'ajax')
                        ->id('task-submit-btn')->class('btn btn-lg btn-info'),
                Former::button('Close')
                        ->setAttribute('data-dismiss', 'modal')->class('btn btn-lg btn-default')

        )?>
        <?= Former::close() ?>
    </div>
@stop
