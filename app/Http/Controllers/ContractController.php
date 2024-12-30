<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Resources\ContractResource;
use App\Http\Resources\PaymentResource;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;

class ContractController extends Controller {
    public function create(Request $request) {


        $request->validate([
                               'type'                   => 'required',
                               'amount'                 => 'required',
                               'currency'               => 'required',
                               'start_at'               => 'required',
                               'end_at'                 => 'required',
                               'auto_renewal'           => 'required',
                               'renew_time_period_type' => 'required',
                               'renew_time_period'      => 'required',
                               'renew_notice'           => 'required',
                               'min_hours'              => 'required',
                               'max_hours'              => 'required',
                               'payment_method'         => 'required',
                               //                               'payment_address'        => 'required',
                               'payment_period'         => 'required',
                               //                               'role'                   => 'required',
                               'contractor_sign_status' => 'required',
                               'user_id'                => 'required',
                               'workspace_id'           => 'required',
                           ]);


        $contract = Contract::create($request->all());


        $payment = Payment::create([
                                       'status'      => 'pending',
                                       'amount'      => NULL,
                                       'total_hours' => NULL,
                                       'type'        => 'salary',
                                       'user_id'     => $request->user_id,
                                       'contract_id' => $contract->id
                                   ]);


        if ($request->schedule_id) {
            $schedule = Schedule::find($request->schedule_id);


            $schedule->update([
                                  'contract_id' => $contract->id
                              ]);
        }


        return api(ContractResource::make($contract));
    }


    public function get(Contract $contract) {
        return api(ContractResource::make($contract));
    }


    public function all() {
        $contracts = Contract::orderBy('id', 'DESC')->get();

        return api(ContractResource::collection($contracts));
    }


    public function userSign(Contract $contract) {
        $user = auth()->user();

        if ($contract->payment_address === NULL) {
            return error('You should add payment address before');
        }
        if ($contract->in_schedule && $contract->schedule === NULL) {
            return error('You should select a schedule first');
        }

        if ($contract->user_sign_status && $contract->contractor_sign_status) {
            return error('Cant revoke sign because contractor has signed the contract');
        }

        if ($contract->user_id === $user->id) {

            $contract->update([
                                  'user_sign_status' => TRUE

                              ]);
        }
        return api(ContractResource::make($contract));
    }

    public function userRevoke(Contract $contract) {
        $user = auth()->user();


        if ($contract->contractor_sign_status) {
            return error('Cant revoke sign because contractor has signed the contract');
        }

        if ($contract->user_id === $user->id) {

            $contract->update([
                                  'user_sign_status' => FALSE

                              ]);
        }
        return api(ContractResource::make($contract));
    }

    public function adminSign(Contract $contract) {
        $user = auth()->user();


        $contract->update([
                              'contractor_sign_status' => TRUE

                          ]);
        return api(ContractResource::make($contract));
    }

    public function adminRevoke(Contract $contract) {

        $user = auth()->user();


        if ($contract->user_sign_status) {
            return error('Cant revoke sign because user has signed the contract');
        }


        $contract->update([
                              'contractor_sign_status' => FALSE

                          ]);
        return api(ContractResource::make($contract));


    }


    public function getAllContents() {
        return api(__('contracts.content'));
    }

    public function payments(Contract $contract) {
        $payments = $contract->payments;


        return api(PaymentResource::collection($payments));
    }


    public function delete(Contract $contract) {

        $user = auth()->user();

        $user->canDo(Permission::DELETE_CONTRACT, $contract->worksapce_id);

        if ($contract->user_sign_status && $contract->contractor_sign_status) {
            return error('Cant delete a signed contract');
        }

        $contract->delete();
        return TRUE;
    }

    public function update(Request $request, Contract $contract) {


        $contract->update($request->except('user_id'));


        $contract->update([
                              'user_sign_status'       => FALSE,
                              'contractor_sign_status' => FALSE,
                          ]);


        if ($request->schedule_id) {
            $schedule = Schedule::find($request->schedule_id);


            $schedule->update([
                                  'contract_id' => $contract->id
                              ]);
        }

        return api(ContractResource::make($contract));

    }
}
