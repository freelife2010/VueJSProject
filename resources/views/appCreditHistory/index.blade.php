@extends('layouts.default')
@include('styles.datatables')
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <br/>
            <div class="panel panel-default">
                <div class="panel-body">
                    <table id="table" class="table table-striped table-hover ">
                        <thead>
                        <tr>
                            <th>Country</th>
                            <th>Destination</th>
                            <th>Code</th>
                            <th>Opentact Sell Rate</th>
                            <th>Your Sell Rate</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?= csrf_field() ?>
@endsection

