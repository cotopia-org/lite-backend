<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {


        $user = $this->user;

        $contract = $this->contract;

        $total_hours = $user->getTime($contract->start_at, $contract->end_at, $contract->workspace_id);
        $amount = $contract->amount * ($total_hours['sum_minutes'] / 60);

        logger($total_hours['sum_minutes']);

        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'amount'      => $amount,
            'total_hours' => $total_hours['sum_minutes'] / 60,
            'bonus'       => $this->bonus,
            'round'       => $this->round,
            'type'        => $this->type,
            'user'        => UserSuperMinimalResource::make($this->user),
            'contract_id' => $contract->id
        ];
    }
}
