@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <?php if (isset($showAppUserSelect) && $showAppUserSelect) : ?>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(350);
            $('[name="app_user_id"]').val($('#app_user').val());
        });
    </script>
    <?php endif; ?>
@stop
@section('modal_body')
    <?= Former::vertical_open()->action($actionUrl) ?>
    <?php if (isset($showAppUserSelect) && $showAppUserSelect) : ?>
        <div style="margin-left: 15px">
            <?= Former::hidden('app_id')->value($APP->id);?>
            <?= Former::hidden('app_user_id');?>
            <?= Former::hidden('id');?>
            <?= Former::select('app_recipient_user_id')
                    ->addOption('Select APP user')
                    ->options($appUsers, 0)
                    ->label('App User')?>
        </div>
    <?php else: ?>
        <?= Former::hidden('app_user_id')->value($appUserId);?>
        <?= Former::hidden('app_recipient_user_id')->value($appRecipientUserId);?>
    <?php endif; ?>
    <div style="clear: both"></div>
    <br/>
    <div class="pull-right">
        <?= Former::actions(
                Former::primary_button($submitLabel)
                        ->type('submit')->setAttribute('data-submit', 'ajax')
                        ->id('task-submit-btn')->class('btn btn-lg btn-info'),
                Former::button('Close')
                        ->setAttribute('data-dismiss', 'modal')->class('btn btn-lg btn-default')

        )?>
        <?= Former::close() ?>
    </div>
@stop
