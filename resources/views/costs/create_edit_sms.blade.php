@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(400);
            $('#countries').chosen();
        });

    </script>
@stop
<link rel="stylesheet" href="{{ asset('vendor/chosen_v1.2.0/chosen.min.css') }}">
@section('modal_body')
    <?php
    $action_url = url("costs/sms-create");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("costs/sms-edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id');?>
        @if (isset($model))
            <?= Former::select('country_id')->options($countries, $model->country_id);?>
        @else
            <?= Former::select('countries[]')->options($countries)
                        ->id('countries')
                        ->setAttribute('data-placeholder', 'Select countries')
                        ->multiple();?>
        @endif
        <?=  Former::text('cents_value')->label('Value (USD, cents)'); ?>
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
