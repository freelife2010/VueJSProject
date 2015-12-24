@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection
@section('scripts')
    <script src="{{ asset('vendor/Chart.js/Chart.js') }}"></script>
    <script src="{{ asset('js/chart.js') }}"></script>
    <script src="{{ asset('vendor/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            var $from_date = $('#from_date');
            var $to_date = $('#to_date');

            $from_date.datetimepicker({
                format: 'DD.MM.YYYY',
                keepOpen: true,
                defaultDate:  $from_date.val()
            }).on('dp.change', function(e) {
                initCharts();
            });
            $to_date.datetimepicker({
                format: 'DD.MM.YYYY',
                keepOpen: true,
                defaultDate:  $to_date.val()
            }).on('dp.change', function(e) {
                initCharts();
            });

        });
    </script>
@endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('vendor/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css') }}">
@endsection
@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="panel panel-default">
                <div class="panel-heading">Manage APP</div>
                <div class="panel-body">
                    <div class="col-md-2 manage-btn">
                        <a href="{{{ URL::to('app-keys/index/?app='.$APP->id) }}}"
                           class="btn btn-labeled btn-xl btn-info">
                        <span class="btn-label">
                               <i class="fa fa-key"></i>
                           </span> APP API keys
                        </a>
                    </div>
                    <div class="col-md-2 manage-btn">
                        <a href="{{{ URL::to('app-users/index/?app='.$APP->id) }}}"
                           class="btn btn-labeled btn-xl btn-info">
                        <span class="btn-label">
                               <i class="fa fa-user"></i>
                           </span> APP Users
                        </a>
                    </div>
                    <div class="col-md-2 manage-btn">
                        <a href="{{{ URL::to('did/index/?app='.$APP->id) }}}"
                           class="btn btn-labeled btn-xl btn-green">
                            <span class="btn-label">
                                   <i class="fa fa-phone-square"></i>
                               </span> Manage DID
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
            <div class='input-group date' id='from_date'>
                <input type='text'
                       value="<?= date('d.m.Y', strtotime('-1 month')) ?>"
                       class="form-control"
                       style="height: 36px"/>
                    <span class="input-group-addon">
                        <span class="fa fa-calendar"></span>
                    </span>
            </div>
        </div>
        <div class="col-md-2">
            <div class='input-group date' id='to_date'>
                <input type='text'
                       value="<?= date('d.m.Y') ?>"
                       class="form-control"
                       style="height: 36px"/>
                    <span class="input-group-addon">
                        <span class="fa fa-calendar"></span>
                    </span>
            </div>
        </div>
    </div>
    <br/>
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><h4>APP CDR Summary</h4></div>
                <div class="panel-body">
                    <div class="col-sm-12">
                        <div class="loader-demo loader-app-cdr">
                            <div class="ball-scale-multiple block-center">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                        <canvas id="chartjs-app-cdr" class="chartjs"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><h4>APP Daily usage</h4></div>
                <div class="panel-body">
                    <div class="col-sm-12">
                        <div class="loader-demo loader-app-daily">
                            <div class="ball-scale-multiple block-center">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                        <canvas id="chartjs-barchart" class="chartjs"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading"><h4>Overall CDR</h4></div>
                <div class="panel-body">
                    <div class="col-sm-12">
                        <div class="loader-demo loader-overall-cdr">
                            <div class="ball-scale-multiple block-center">
                                <div></div>
                                <div></div>
                                <div></div>
                            </div>
                        </div>
                        <canvas id="chartjs-overall-cdr" class="chartjs"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
