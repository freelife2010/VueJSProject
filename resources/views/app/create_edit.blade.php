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
        $submit_label = 'Сохранить';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::text('name')->label('Name');?>
    </div>
    <div style="clear: both"></div>
    <br/>
    <div class="modal-footer">
        <?= Former::actions(
                Former::primary_button($submit_label)
                        ->type('submit')->setAttribute('data-submit', 'ajax')->id('task-submit-btn'),
                Former::button('Close')->setAttribute('data-dismiss', 'modal')
        )?>
        <?= Former::close() ?>
    </div>
@stop
