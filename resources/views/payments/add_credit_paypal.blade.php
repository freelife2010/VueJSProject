@extends('partials.modal')
@section('title')
    <em class="icon-plus"></em>&nbsp; Add credit
    <script src="{{asset('vendor/jquery.inputmask/dist/jquery.inputmask.bundle.min.js')}}"></script>
    <script>
        $(function () {
            setModalWidth(350);
            $('#credit_card').inputmask({"mask" : "9999 9999 9999 9999"});
            $('#cvc').inputmask("9{3,4}");
            $('#amount').inputmask('9{0,4}');

            $('#submitBtn').click(function(event) {
                var $form = $(this).parents('form');

                $form.validator('validate');
                var isValid = !$form.find('.has-error').length;
                if (isValid) {

//                    postForm($('#submitBtn'), $form, $form.attr('action'));
                    $('#submitBtn').prop('disabled', true);
                    $form.submit();
                }


                // Prevent the form from submitting with the default action
                return false;
            });
        });
    </script>

@stop
@section('modal_body')
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            {!! Form::open(['url' => url('payments/create-paypal'),
            'data-toggle' => 'validator',
            'id' => 'payment-form']) !!}

            <div class="form-group" id="first-name-group">
                {!! Form::label('amount', 'Amount:') !!}
                {!! Form::text('amount', null, [
                'required' => 'required',
                'class' => 'form-control']) !!}
            </div>

            <br/>
            <div class="form-group text-right">
                {!! Form::button('Add credit', [
                'class'       => 'btn btn-info btn-lg btn-order',
                'type'         => 'submit',
                'id'          => 'submitBtn',
                'style'       => 'margin-bottom: 10px;']) !!}
                {!! Form::button('Close',
                ['class' => 'btn btn-default btn-lg btn-order',
                 'data-dismiss' => 'modal',
                'style' => 'margin-bottom: 10px;']) !!}
            </div>

            {!! Form::close() !!}

        </div>
    </div>

@stop