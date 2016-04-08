@extends('partials.modal')
@section('title')
    <em class="icon-trash"></em>&nbsp;  {{$title}}
    <script type="text/javascript">
        $(document).ready(function () {
            setModalWidth(220);
        });
    </script>
@stop
@section('modal_body')
    <form id="deleteForm" class="form-horizontal" method="post"
          action="{{ URL::to($url) }}"
          autocomplete="off">

        <input type="hidden" name="_token" value="{{{ csrf_token() }}}" /> <input
                type="hidden" name="id" value="{{ $model->id }}" />
        <div class="controls">
            <br>
            <button type="submit" class="btn btn-lg btn-danger"
                    data-submit="ajax">
                 Delete
            </button>
            <element data-dismiss="modal" class="btn btn-lg btn-warning close_popup">
                Cancel</element>
        </div>
    </form>
@stop
