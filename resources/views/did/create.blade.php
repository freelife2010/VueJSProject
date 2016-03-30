@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            var $state = $('#state');
            var $did_action = $('#action');
            setModalWidth(400);
            $state.change(function() {
                var $rate_center = $('#rate_center');
                var $did = $('#did');
                $rate_center.prop('disabled', true);
                $did.prop('disabled', true);
                $did_action.prop('disabled', true);
                var ajaxCallback = function(data) {
                    $rate_center.replaceWith(data);
                    $rate_center.prop('disabled', false);
                    bindRateCenterEvent();
                    getNumbers();
                };
                var params = {
                    'state': $('#state option:selected').text()
                };
                ajaxGetData('{{ url('did/cities?app='.$APP->id) }}', params, ajaxCallback)
            });
            bindRateCenterEvent();
            bindActionSelectEvent($did_action);
            bindOutsideNumberClick($state, $did_action);
        });

        function bindRateCenterEvent() {
            var $rate_center = $('#rate_center');
            $rate_center.change(function() {
                getNumbers();
            });
        }

        function bindActionSelectEvent($did_action) {
            $did_action.change(function() {
                getParameters($did_action);
            });
        }

        function bindOutsideNumberClick($state, $did_action) {
            var $rate_center = $('#rate_center');
            var $did = $('#did');
            var $outsideNumCheckbox = $('#outside_number_checkbox');
            var $outsideNumber = $('#outside_number');
            $outsideNumCheckbox.click(function(e) {
                $state.prop('disabled', $outsideNumCheckbox.prop('checked'));
                $rate_center.prop('disabled', ($outsideNumCheckbox.prop('checked')));
                $did.prop('disabled', ($outsideNumCheckbox.prop('checked')));
                $outsideNumber.prop('disabled', !$outsideNumCheckbox.prop('checked'));
                $did_action.prop('disabled', (!$outsideNumCheckbox.prop('checked') && !$state.val()));
            });

        }

        function getNumbers() {
            var $did = $('#did');
            var $did_action = $('#action');
            $did.prop('disabled', true);
            $did_action.prop('disabled', true);
            var ajaxCallback = function(data) {
                $did.replaceWith(data);
                $did.prop('disabled', false);
                $did_action.prop('disabled', false);
            };
            var params = {
                'state': $('#state option:selected').text(),
                'rate_center': $('#rate_center option:selected').text()
            };
            ajaxGetData('{{ url('did/numbers?app='.$APP->id) }}', params, ajaxCallback)
        }

        function getParameters() {
            var $paramsDiv = $('#action_parameters');
            var ajaxCallback = function(data) {
                $paramsDiv.html(data);
            };
            var params = {
                'did_action': $('#action option:selected').val()
            };
            ajaxGetData('{{ url('did/parameters?app='.$APP->id) }}', params, ajaxCallback)

        }

        function ajaxGetData(url, params, success) {
            $.ajax({
                url: url,
                type: 'GET',
                data: params,
                success: success
            })
        }
    </script>
@stop
@section('modal_body')
    <?php
    $action_url = url("did/create");
    $submit_label = 'Buy';
    $edit = false;
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
        <?= Former::select('owned_by')->options($appUsers)->label('APP User')
                ->placeholder('Select APP User');?>
        <?= Former::select('state')->options($states)->placeholder('Select state');?>
        <?= Former::select('rate_center')->disabled();?>
        <?= Former::select('did')->disabled();?>
        <?= Former::checkbox('outside_number_checkbox')->raw();?>
        <?= Former::label('Outside number')->for('outside_number_checkbox');?>
        <?= Former::text('outside_number')->disabled()->raw() ?><br/>
        <?= Former::select('action')->id('action')->options($actions)
                    ->placeholder('Select action')
                    ->disabled();?>
        <div id="action_parameters"></div>
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
