<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'message_id'       => $this->message_id,
            'chat_id'          => $this->chat_id,
            'start_position'   => $this->start_position,
            'mentionable_type' => $this->mentionable_type,
            'mentionable_id'   => $this->mentionable_id,
            'created_at'       => $this->created_at,
        ];
    }
}
