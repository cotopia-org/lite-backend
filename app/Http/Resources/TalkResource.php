<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalkResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'         => $this->id,
            'user'       => UserMinimalResource::make($this->user),
            'owner'      => UserMinimalResource::make($this->owner),
            'response'   => $this->response,
            'type'       => $this->type,
            'created_at' => $this->created_at,
        ];
    }
}
