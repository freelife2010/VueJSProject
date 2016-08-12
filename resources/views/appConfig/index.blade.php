@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection

@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <form action="app-config/save-config" method="post">
        <div class="form-group">
            <label for="icx_cc_rate_table" class="control-label">Standard ICX CC Rate Table Id</label>
            <input class="form-control" id="icx_cc_rate_table" type="text" name="icx_cc_rate_table" value="{{ isset($configValues['icx_cc_rate_table']) ? $configValues['icx_cc_rate_table'] : 0 }}">
        </div>

        <div class="form-group">
            <label for="az_rate_table" class="control-label">A-Z Rate Table</label>
            <input class="form-control" id="az_rate_table" type="text" name="az_rate_table" value="{{ isset($configValues['az_rate_table']) ? $configValues['az_rate_table'] : '' }}">
        </div>

        <div class="form-group">
            <label for="cc_rate_table" class="control-label">CC Rate Table</label>
            <input class="form-control" id="cc_rate_table" type="text" name="cc_rate_table" value="{{ isset($configValues['cc_rate_table']) ? $configValues['cc_rate_table'] : '' }}">
        </div>

        <div class="form-group">
            <label for="az_gateway_ip" class="control-label">A-Z Gateway IP</label>
            <input class="form-control" id="az_gateway_ip" type="text" name="az_gateway_ip" value="{{ isset($configValues['az_gateway_ip']) ? $configValues['az_gateway_ip'] : '127.0.0.1' }}">
        </div>

        <div class="form-group">
            <label for="cc_gateway_ip" class="control-label">CC Gateway IP</label>
            <input class="form-control" id="cc_gateway_ip" type="text" name="cc_gateway_ip" value="{{ isset($configValues['cc_gateway_ip']) ? $configValues['cc_gateway_ip'] : '127.0.0.1' }}">
        </div>

        <div class="form-group">
            <label for="freeswitch_ip" class="control-label">Freeswitch IP</label>
            <input class="form-control" id="freeswitch_ip" type="text" name="freeswitch_ip" value="{{ isset($configValues['freeswitch_ip']) ? $configValues['freeswitch_ip'] : '127.0.0.1' }}">
        </div>

        <div class="form-group">
            <label for="route_strategy_id" class="control-label">Route Strategy Id</label>
            <input class="form-control" id="route_strategy_id" type="text" name="route_strategy_id" value="{{ isset($configValues['route_strategy_id']) ? $configValues['route_strategy_id'] : '1' }}">
        </div>

        <div class="pull-right">
            <div>
                <button class="btn btn-lg btn-info" data-submit="ajax" id="task-submit-btn" type="submit">Save</button>
            </div>
        </div>
    </form>
@endsection
