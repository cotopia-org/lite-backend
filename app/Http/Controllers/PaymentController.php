<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;

class PaymentController extends Controller {
    public function all() {
        $payments = Payment::orderBy('id', 'DESC')->whereHas('contract',function($query){
            $query->where('user_sign_status',true)->where('contractor_sign_status',true);
        })->get();

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
                           ]);


        $user = User::find($request->user_id);


        $contract_id = $user->contracts()->orderBy('id', 'desc')->first()->id;

        $payment = Payment::create([
                                       'status'      => $request->status,
                                       'amount'      => $request->amount,
                                       'total_hours' => $request->total_hours,
                                       'bonus'       => $request->bonus ?? 0,
                                       'round'       => $request->round ?? 0,
                                       'type'        => $request->type,
                                       'user_id'     => $request->user_id,
                                       'contract_id' => $contract_id,
                                   ]);


        return api(PaymentResource::make($payment));
    }


    public function update(Request $request, Payment $payment) {


        $payment->update([
                             'status' => $request->status,
                             'amount' => $request->amount,
                             'bonus'  => $request->bonus ?? 0,
                             'round'  => $request->roun ?? 0
                         ]);


        return api(PaymentResource::make($payment));
    }
}
