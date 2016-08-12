@extends('layouts.default')
@section('title')
    {{ $title }} :: @parent
@endsection

@section('subtitle') {{ $subtitle }} @stop
@section('content')
    <form action="/app-config/save-google-api-data" method="post">
        <input type="hidden" name="user_id" value="{{$userId}}">

        <div class="form-group">
            <label for="project_id" class="control-label">Project Id</label>
            <input class="form-control" id="project_id" type="text" name="project_id" value="{{ isset($googleApiData->project_id) ? $googleApiData->project_id : '' }}">
        </div>

        <div class="form-group">
            <label for="private_key_id" class="control-label">Private Key Id</label>
            <input class="form-control" id="private_key_id" type="text" name="private_key_id" value="{{ isset($googleApiData->private_key_id) ? $googleApiData->private_key_id : '' }}">
        </div>

        <div class="form-group">
            <label for="private_key" class="control-label">Private Key</label>
            <textarea class="form-control" id="private_key" name="private_key">{{ isset($googleApiData->private_key) ? $googleApiData->private_key : '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="client_email" class="control-label">Client Email</label>
            <input class="form-control" id="client_email" type="text" name="client_email" value="{{ isset($googleApiData->client_email) ? $googleApiData->client_email : '' }}">
        </div>

        <div class="form-group">
            <label for="client_id" class="control-label">Client Id</label>
            <input class="form-control" id="client_id" type="text" name="client_id" value="{{ isset($googleApiData->client_id) ? $googleApiData->client_id : '' }}">
        </div>

        <div class="form-group">
            <label for="client_x509_cert_url" class="control-label">Client x509 Certificate Id</label>
            <input class="form-control" id="client_x509_cert_url" type="text" name="client_x509_cert_url" value="{{ isset($googleApiData->client_x509_cert_url) ? $googleApiData->client_x509_cert_url : '' }}">
        </div>

        <div class="pull-right">
            <div>
                <button class="btn btn-lg btn-info" data-submit="ajax" id="task-submit-btn" type="submit">Save</button>
            </div>
        </div>
    </form>
@endsection
