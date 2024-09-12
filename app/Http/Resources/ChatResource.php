<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {

        $user = auth()->user();
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'workspace_id'       => $this->workspace_id,
            'participants'       => UserMinimalResource::collection($this->participants()),
            'last_message'       => MessageResource::make($this->lastMessage()),
            'unseens'            => $this->unSeens($user)->count(),
            'pinned_messages'    => MessageResource::make($this->pinnedMessages()),
            'mentioned_messages' => MessageResource::make($this->mentionedMessages($user)),
        ];
    }
}
