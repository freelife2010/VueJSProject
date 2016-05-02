@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("app/create");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("app/edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id');?>
        <?= Former::text('name')->label('Name');?>
        <?= Former::text('alias')->label('Alias');?>
        <?= Former::select('presence')->options($statuses)->label('Status');?>
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
