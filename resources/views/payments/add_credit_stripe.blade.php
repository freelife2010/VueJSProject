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
        })
    </script>

    <script>
        // This identifies your website in the createToken call below
        Stripe.setPublishableKey('{!! env('STRIPE_PK') !!}');

        jQuery(function($) {
            $('#submitBtn').click(function(event) {
                var $form = $(this).parents('form');

                $form.validator('validate');
                var isValid = !$form.find('.has-error').length;
                if (isValid) {
                    $form.find('#submitBtn').prop('disabled', true);

                    Stripe.card.createToken($form, stripeResponseHandler);
                }


                // Prevent the form from submitting with the default action
                return false;
            });
        });

        function stripeResponseHandler(status, response) {
            var $form = $('#payment-form');

            if (response.error) {
                // Show the errors on the form
                var options = {
                    status: 'danger'
                };
                var text = response.error.message;
                $.notify(text, options || {});
                $('#submitBtn').button('reset').prop('disabled', false);
            } else {
                // response contains id and card, which contains additional card details
                var token = response.id;
                // Insert the token into the form so it gets submitted to the server
                $('input[name=stripeToken]').val(token);
                // and submit
                postForm($('#submitBtn'), $form, $form.attr('action'));
            }
        };
    </script>
@stop
@section('modal_body')
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            {!! Form::open(['url' => url('payments/create-stripe'),
            'data-toggle' => 'validator',
            'id' => 'payment-form']) !!}

            <div class="form-group" id="first-name-group">
                {!! Form::label('amount', 'Amount:') !!}
                {!! Form::text('amount', null, [
                'required' => 'required',
                'class' => 'form-control']) !!}
            </div>

            <div class="form-group">
                {!! Form::label(null, 'Credit card number:') !!}
                {!! Form::text(null, null, [
                'class' => 'form-control',
                'data-stripe' => 'number',
                'id' => 'credit_card',
                'required' => 'required',]) !!}
            </div>

            <div class="form-group">
                {!! Form::label(null, 'Card Validation Code (3 or 4 digit number):') !!}
                {!! Form::text(null, null, ['class' => 'form-control',
                'id' => 'cvc',
                'required' => 'required',
                'data-stripe' => 'cvc']) !!}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label(null, 'Ex. Month') !!}
                        {!! Form::selectMonth(null, null, [
                        'class' => 'form-control',
                        'data-stripe' => 'exp-month'
                        ], '%m') !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label(null, 'Ex. Year') !!}
                        {!! Form::selectYear(null, date('Y'), date('Y') + 10, null,
                        ['class' => 'form-control',
                         'data-stripe' => 'exp-year']) !!}
                    </div>
                </div>
            </div>
            <br/>
            <?= Form::hidden('stripeToken') ?>
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