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
    $action_url = url("queues/create");
    $submit_label = 'Create';
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("queues/edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open_for_files()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id');?>
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::text('queue_name');?>
        <?= Former::text('client_waiting_prompt');?>
	<?= Former::file('client_waiting_audio');?>
        <?= Former::text('agent_waiting_prompt');?>
	<?= Former::file('agent_waiting_audio');?>
        <?= Former::hidden('created_by')->value(Auth::user()->id);?>
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
