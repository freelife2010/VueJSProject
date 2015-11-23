@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            var $state = $('#state');
            var $rate_center = $('#rate_center');
            $state.change(function() {
                $rate_center = $('#rate_center');
                $rate_center.prop('disabled', true);
                var ajaxCallback = function(data) {
                    $rate_center.replaceWith(data);
                    $rate_center.prop('disabled', false);
                    bindRateCenterEvent();
                    getNumbers();
                };
                var params = {
                    'state': $('#state option:selected').text()
                };
                ajaxGetData('did/cities', params, ajaxCallback)
            });
            bindRateCenterEvent();
        });

        function bindRateCenterEvent() {
            var $rate_center = $('#rate_center');
            $rate_center.change(function() {
                getNumbers();
            });
        }

        function getNumbers() {
            var $did = $('#did');
            $did.prop('disabled', true);
            var ajaxCallback = function(data) {
                $did.replaceWith(data);
                $did.prop('disabled', false);
            };
            var params = {
                'state': $('#state option:selected').text(),
                'rate_center': $('#rate_center option:selected').text()
            };
            ajaxGetData('did/numbers', params, ajaxCallback)
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
        <?= Former::select('state')->options($states)->placeholder('Select state');?>
        <?= Former::select('rate_center')->disabled();?>
        <?= Former::select('did')->disabled();?>
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
