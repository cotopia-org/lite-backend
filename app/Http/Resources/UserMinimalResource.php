<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMinimalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $last_login = $this->activities()->orderBy('id', 'DESC')->first();
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'username'    => $this->username,
            'status'      => $this->status,
            'avatar'      => FileResource::make($this->avatar),
            'coordinates' => $this->coordinates,
            'last_login'  => $last_login?->join_at,
            'verified'    => $this->verified ?? FALSE,


        ];
    }
}
