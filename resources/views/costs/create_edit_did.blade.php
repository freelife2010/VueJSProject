@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            setModalWidth(400);
            var country = $('#country_id');
            var $value = $('#value, #one_time_value, #per_month_value');
            var usStates = $('#us_states');
            var usRateCenterDiv = $('#us_rate_center');
            bindStateEvent();
            country.change(function() {
                $value.prop('disabled', true);
                if (country.val() == 1)
                    getDidStates();
                else {
                    usStates.html('');
                    usRateCenterDiv.html('');
                    $value.prop('disabled', false);
                }
            });

            function getDidStates() {
                var ajaxCallback = function(data) {
                    usStates.html(data);
                    bindStateEvent();
                };
                var params = {
                };
                ajaxGetData('{{ url('costs/did-states') }}', params, ajaxCallback)
            }

            function ajaxGetData(url, params, success) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: params,
                    success: success
                })
            }

            function bindStateEvent() {
                var $state = $('#state');
                var $value = $('#value');
                $state.change(function () {
                    var $rate_center = usRateCenterDiv.find('select');
                    $rate_center.prop('disabled', true);
                    $value.prop('disabled', true);
                    var ajaxCallback = function (data) {
                        usRateCenterDiv.html(data);
                        $value.prop('disabled', false);
                    };
                    var params = {
                        'state': $('#state option:selected').text()
                    };
                    ajaxGetData('{{ url('costs/did-cities') }}', params, ajaxCallback)
                });
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
        <?= Former::select('country_id')
                ->label('Country')
                ->options($countries)
                ->placeholder('Select country');?>
        <div id="us_states">
            @if (isset($model) and $model->country_id == \App\Models\Country::COUNTRY_US_ID)
                <?= Former::select('state')->options($states, $states[$model->state]);?>
            @endif
        </div>
        <div id="us_rate_center">
            @if (isset($model) and $model->country_id == \App\Models\Country::COUNTRY_US_ID)
                <?= Former::text('rate_center')->readonly();?>
            @endif
        </div>
        <br/>
        <?php
            $former = Former::text('value')->label('Value (USD)');
            echo isset($model) ? $former : $former->disabled();

            $former = Former::text('one_time_value')->label('Value (USD) - One Time');
            echo isset($model) ? $former : $former->disabled();

            $former = Former::text('per_month_value')->label('Value (USD) - Monthly');
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
