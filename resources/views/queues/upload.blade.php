@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
        });
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("queues/upload");
    $submit_label = 'Upload';
    ?>
    <?= Former::vertical_open_for_files()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id')->value($id);?>
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::file('client_waiting_audio');?>
        <?= Former::file('agent_waiting_audio');?>
    </div>
    <div style="clear: both"></div>
    <br/>
    <div class="pull-right">
        <?= Former::actions(
                Former::primary_button($submit_label)
                        ->type('submit')//->setAttribute('data-submit', 'ajax')
                        ->id('task-submit-btn')->class('btn btn-lg btn-info'),
                Former::button('Close')
                        ->setAttribute('data-dismiss', 'modal')->class('btn btn-lg btn-default')

        )?>
        <?= Former::close() ?>
    </div>
@stop
