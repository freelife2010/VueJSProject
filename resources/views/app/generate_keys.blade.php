@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            $('#phone').inputmask('+9 (999) 999-99-99')
        });
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("app/generate-keys");
    $submit_label = 'Generate';
    $edit = false;
    if (isset($appKey)) {
        Former::populate($appKey);
        $action_url   = url("app/regenerate-keys/$appKey->id");
        $submit_label = 'Regenerate';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        @if(isset($appKey) and $appKey->isExpired())
            <div role="alert" class="alert alert-warning">
                <strong>Warning!</strong> API keys are expired!
            </div>
        @endif
        <?= Former::hidden('app_id')->value($model->id);?>
        <?= Former::text('id')->disabled();?>
        <?= Former::text('secret')->disabled();?>
        <?= Former::text('expire_days')->label('Expired in (days)')->value(5);?>
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
