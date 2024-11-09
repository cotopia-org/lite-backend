<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSuperMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'avatar'   => FileResource::make($this->avatar),
            'name'     => $this->name,
            'username' => $this->username,
            'status'   => $this->status,

        ];
    }
}
