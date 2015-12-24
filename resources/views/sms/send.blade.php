@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            $('#users').chosen();
        });
    </script>
@stop
<link rel="stylesheet" href="{{ asset('vendor/chosen_v1.2.0/chosen.min.css') }}">
@section('modal_body')
    <?php
    $action_url = url("sms/send");
    $submit_label = 'Send';
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::select('users[]')->options($users)->id('users')
                ->multiple()->setAttribute('data-placeholder', 'Select users');?>
        <?= Former::textArea('message')->rows(7);?>
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
