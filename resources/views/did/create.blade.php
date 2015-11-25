@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            var $state = $('#state');
            var $did_action = $('#did_action');
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

        function getNumbers() {
            var $did = $('#did');
            var $did_action = $('#did_action');
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
                'did_action': $('#did_action option:selected').val()
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
        <?= Former::select('state')->options($states)->placeholder('Select state');?>
        <?= Former::select('rate_center')->disabled();?>
        <?= Former::select('did')->disabled();?>
        <?= Former::select('action')->id('did_action')->options($actions)
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
