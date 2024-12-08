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


        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'amount'      => $this->amount,
            'total_hours' => $this->total_hours,
            'bonus'       => $this->bonus,
            'round'       => $this->round,
            'type'        => $this->type,
            'user'        => UserSuperMinimalResource::make($this->user),
            'contract_id' => $contract->id
        ];
    }
}
