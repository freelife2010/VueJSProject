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
    $action_url = url("conferences/create");
    $submit_label = 'Create';
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("conferences/edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::hidden('id');?>
        <?= Former::text('name');?>
        <?= Former::text('host_pin')->type('number');?>
        <?= Former::text('guest_pin')->type('number');?>
        <?= Former::text('greeting_prompt');?>
        <?= Former::hidden('owner_user_id')->value(Auth::user()->id);?>
        <?= Former::hidden('created_on')->value(date('Y-m-d H:i:s'));?>
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
