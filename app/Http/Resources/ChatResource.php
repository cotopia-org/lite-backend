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


    // TODO: We must change pinned messages from here to got with request. and participants too.
    public function toArray(Request $request): array {

        $user = auth()->user();
        return [
            'id'           => $this->id,
            'title'        => $this->getTitle($user),
            'workspace_id' => $this->workspace_id,
            'participants' => UserMinimalResource::collection($this->users),
            'last_message' => MessageResource::make($this->lastMessage),
            'unseens'      => $this->unSeensCount($user),
            //            'pinned_messages'    => MessageResource::collection($this->pinnedMessages()),


            'mentioned_messages' => MessageResource::collection($this->mentionedMessages($user)),
        ];
    }
}
