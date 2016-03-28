@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            var $callerInputBlock = $('#caller_id_block');
            $('#phone').inputmask('+9{1,3}(999) 999-99-99');
            $('#set_password').click(function() {
               var $password = $('#password');
                $password.prop('disabled', !$password.prop('disabled'));
            });
            $('#allow_outgoing_call').click(function() {
                if ($(this).prop('checked')) {
                    $.ajax({
                        url: '{{ url('app-users/caller-id-inputs?app='.$APP->id) }}',
                        success: function(data) {
                            $callerInputBlock.html(data);
                            $callerInputBlock.removeClass('hide');
                            bindCallerIdEvent();
                        }
                    });
                } else $callerInputBlock.addClass('hide');
                var $callerId = $('#caller_id');
                $callerId.prop('disabled', !$callerId.prop('disabled'));
            });

            function bindCallerIdEvent()
            {
                var $customId = $('#caller_id_custom');
                $('#caller_id').change(function() {
                   if ($(this).val() != 0)
                       $customId.prop('disabled', true);
                   else $customId.prop('disabled', false);
                });
            }

        });
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("app-users/create?app=$APP->id");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("app-users/edit/$model->id?app=$APP->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::hidden('id');?>
        <?= Former::text('name')->label('Name');?>
        @if(isset($model))
            <?= Former::checkbox('set_password')->raw();?>
            <?= Former::label('Set new password')->for('set_password');?>
            <?= Former::text('password')->type('password')->label('')->disabled();?>
        @else
            <?= Former::text('password')->type('password')->label('Password');?>
        @endif
        <?= Former::text('email')->label('E-mail');?>
        <?= Former::text('phone')->label('Phone');?>
        <?= Former::checkbox('allow_outgoing_call')->style('margin-left: 0')->raw();?>
        <?= Former::label('Allow outgoing call')->style('margin-left: 20px')->for('allow_outgoing_call');?>
        <br/>
        <did id="caller_id_block" class="hide">

        </did>
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
