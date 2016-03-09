@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            $('#phone').inputmask('+9 (999) 999-99-99');
            $('#set_password').click(function() {
               var $password = $('#password');
                $password.prop('disabled', !$password.prop('disabled'));
            });
        });
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("users/create");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("users/edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id');?>
        <?= Former::text('name')->label('Name');?>
        <?= Former::text('email')->label('E-mail');?>
        @if(isset($model))
            <?= Former::checkbox('set_password')->raw();?>
            <?= Former::label('Set new password')->for('set_password');?>
            <?= Former::text('password')->type('password')->label('')->disabled();?>
        @else
            <?= Former::text('password')->type('password')->label('Password');?>
        @endif
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
