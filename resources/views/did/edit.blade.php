@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script type="text/javascript">
        $(document).ready(function() {
            var $did_action = $('#did_action');
            setModalWidth(400);
            bindActionSelectEvent($did_action);
        });


        function bindActionSelectEvent($did_action) {
            $did_action.change(function() {
                getParameters($did_action);
            });
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
    $action_url = url("did/edit");
    Former::populate($model);
    $submit_label = 'Buy';
    $edit = false;
    ?>
    <?= Former::vertical_open()->action($action_url) ?>
    <div style="margin-left: 15px">
        <?= Former::hidden('app_id')->value($APP->id);?>
            <?= Former::select('owned_by')->options($appUsers, $model->owned_by)
                    ->label('APP User')->disabled();?>
        <?= Former::select('state')->options(["$model->state"])->disabled();?>
        <?= Former::select('rate_center')->options(["$model->rate_center"])->disabled();?>
        <?= Former::select('did')->options(["$model->did"])->disabled();?>
        <?= Former::select('action')->id('did_action')->options($actions, $model->action_id)
                        ->disabled();?>
        <div id="action_parameters">
            <?php
                if (!empty($params)) {
                    echo Former::label('Action parameter(s)');
                    $method = 'text';
                    foreach ($params as $param) {
                        $selectName = "parameters[$param->id]";
                        if ($param->name == 'Key-Action')
                            $method = 'textarea';
                        echo Former::$method($selectName)->value($param->parameter_value)
                                ->help($param->name)->label('')->disabled();
                    }
                }
            ?>
        </div>
    </div>
    <div style="clear: both"></div>
    <br/>
    <div class="pull-right">
        <?= Former::actions(
                Former::button('Close')
                        ->setAttribute('data-dismiss', 'modal')->class('btn btn-lg btn-default')

        )?>
        <?= Former::close() ?>
    </div>
@stop
