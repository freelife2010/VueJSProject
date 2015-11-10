@extends('partials.modal')
<link rel="stylesheet" href="{{ asset('vendor/bootstrap-fileinput/css/fileinput.min.css') }}">
@section('title')
    <em class="icon-plus"></em>&nbsp; {{$title}}
    <script src="{{asset('vendor/bootstrap-fileinput/js/fileinput.min.js')}}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var fileinput = $('#fileinput');
            setModalWidth(500);
            fileinput.fileinput({
                uploadIcon: '<em class="fa fa-upload"></em> ',
                removeIcon: '<em class="fa fa-remove"></em> ',
                language: "ru",
                showPreview: false,
                initialCaption: 'Users data sheet',
                uploadUrl: "{{ url('app-users/import?app='.$APP->id) }}",
                allowedFileExtensions: ['csv', 'xls', 'xlsx'],
                allowedPreviewTypes: ['html'],
                dropZoneEnabled: false,
                maxFileSize: '5000',
                maxFileCount: 1,
                uploadExtraData: function (previewId, index) {
                    var obj = {};
                    obj.app_id   = $('#app_id').val();
                    obj.email    = $('#email').val();
                    obj.username = $('#username').val();
                    obj.password = $('#password').val();
                    return obj;
                },
                ajaxSettings: {
                    error: function(data) {
                        fileinput.fileinput('unlock');
                        fileinput.fileinput('clear');
                        showErrorMessage(data, $('#upload-form'));
                    },
                    success: function(data) {
                        var type = data.error == 0 ? 'success' : 'danger';
                        fileinput.fileinput('unlock');
                        fileinput.fileinput('clear');
                        showMessage(type, data.alert);
                        reloadTables();
                    }
                }
            });
        });
    </script>
@stop
@section('modal_body')
    <?= Former::vertical_open()->enctype('multipart/form-data')->id('upload-form') ?>
    <div style="margin-left: 15px">
        <label for="fileinput" class="control-label">Choose file to upload:</label>
        <input type="file" name="sheet_file" multiple="" id="fileinput">
        <br/>
        <?= Former::hidden('app_id')->id('app_id')->value($APP->id)?>
        <?= Former::text('email')->label('E-mail column (unique field)')->value('email');?>
        <?= Former::text('username')->label('Username column')->value('username');?>
        <?= Former::text('password')->label('Password column')->value('password');?>
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
