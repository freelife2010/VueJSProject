<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['account_id', 'amount', 'stripe_transaction_id'];

    public static function createStripePayment($charge)
    {
        $payment = new Payment();
        $payment->account_id = \Auth::user()->id;
        $payment->amount = $charge->amount;
        $payment->transaction_id = $charge->balance_transaction;
        $payment->type = 'stripe';
        $payment->save();
    }
}
