@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(400);
            var $state = $('#state');
            var $value = $('#value');
            $state.change(function() {
                var $rate_center = $('#rate_center');
                $rate_center.prop('disabled', true);
                $value.prop('disabled', true);
                var ajaxCallback = function(data) {
                    $rate_center.replaceWith(data);
                    $rate_center.prop('disabled', false);
                    $value.prop('disabled', false);
                };
                var params = {
                    'state': $('#state option:selected').text()
                };
                ajaxGetData('{{ url('costs/did-cities') }}', params, ajaxCallback)
            });

            function ajaxGetData(url, params, success) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: params,
                    success: success
                })
            }
        });

    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("costs/did-create");
    $submit_label = 'Create';
    $edit = false;
    if (isset($model)) {
        Former::populate($model);
        $action_url   = url("costs/did-edit/$model->id");
        $submit_label = 'Save';
        $edit         = true;
    }
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('id');?>
        <?= Former::select('state')->options($states)->placeholder('Select state');?>
        @if (isset($model))
            <?= Former::text('rate_center')->readonly();?>
        @else
            <?= Former::select('rate_center')->disabled();?>
        @endif
        <?php
            $former = Former::text('value')->label('Value (USD)');

            echo isset($model) ? $former : $former->disabled();
        ?>
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
