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
                               'status'=>'required',
                               'amount'=>'required',
                               'total_hours',
                               'bonus',
                               'round',
                               'type',
                               'user_id',
                               'contract_id'
                           ]);
    }
}
