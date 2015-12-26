@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            $('#scopes').chosen();
        });
    </script>
@stop
<link rel="stylesheet" href="{{ asset('vendor/chosen_v1.2.0/chosen.min.css') }}">
@section('modal_body')
    <?php
    $action_url = url("app-keys/create");
    $submit_label = 'Generate';
    $edit = false;
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($model->id);?>
        <?= Former::text('id')->disabled();?>
        <?= Former::text('secret')->disabled();?>
        <?= Former::select('scopes[]')->options($scopes)
                ->multiple()->setAttribute('data-placeholder', 'Select permitted APIs')
                ->id('scopes')
                ->label('API');?>
        <?= Former::text('expire_days')->label('Expires in (days)')->value(5);?>
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
