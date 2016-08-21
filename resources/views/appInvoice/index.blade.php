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
                            <th>Invoice Date</th>
                            <th>Invoicing for (Month)</th>
                            <th>Invoicing Amount</th>
                            <th>Due Date</th>
                            <th>Action</th>
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

