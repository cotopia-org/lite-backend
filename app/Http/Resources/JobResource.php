<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'end_at'       => $this->end_at,
            'total_hours'  => $this->users->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'hours'   => $this->getTime($user)['sum_hours']
                ];
            }),
            'members'      => UserMinimalResource::collection($this->users),
        ];
    }
}
