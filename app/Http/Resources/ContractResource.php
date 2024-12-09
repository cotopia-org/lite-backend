<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'                     => $this->id,
            'type'                   => $this->type,
            'amount'                 => $this->amount,
            'currency'               => $this->currency,
            'start_at'               => $this->start_at,
            'end_at'                 => $this->end_at,
            'auto_renewal'           => $this->auto_renewal,
            'renewal_count'          => $this->renewal_count,
            'renew_time_period_type' => $this->renew_time_period_type,
            'renew_time_period'      => $this->renew_time_period,
            'renew_notice'           => $this->renew_notice,
            'user_status'            => $this->user_status,
            'contractor_status'      => $this->contractor_status,
            'min_hours'              => $this->min_hours,
            'max_hours'              => $this->max_hours,
            'payment_method'         => $this->payment_method,
            'payment_address'        => $this->payment_address,
            'payment_period'         => $this->payment_period,
            'role'                   => $this->role,
            'user_sign_status'       => $this->user_sign_status,
            'contractor_sign_status' => $this->contractor_sign_status,
            'user_id'                => $this->user_id,
            'workspace_id'           => $this->workspace_id,
            'text'                   => $this->text,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
