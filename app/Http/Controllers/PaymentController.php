<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller {
    public function all() {
        $payments = Payment::all();

        return api(PaymentResource::collection($payments));
    }


    public function create(Request $request) {

        $request->validate([
                               'status' => 'required',
                               'amount' => 'required',
                               'total_hours',
                               'bonus',
                               'round',
                               'type',
                               'user_id',
                               'contract_id'
                           ]);


        $payment = Payment::create($request->all());


        return api(PaymentResource::make($payment));
    }


    public function update(Request $request, Payment $payment) {


        $payment->update([
                             'status' => $request->status,
                             'amount' => $request->amount,
                             'bonus'  => $request->bonus,
                             'round'  => $request->round
                         ]);


        return api(PaymentResource::make($payment));
    }
}
