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
    // TODO: Mentions will added after refactor in model. check the comment in mentionedMessages in Chat Model.
    public function toArray(Request $request): array {

        $user = auth()->user();
        $pivot = $this->pivot;
        return [
            'id'           => $this->id,
            'title'        => $this->getTitle($user),
            'workspace_id' => $this->workspace_id,
            'participants' => UserMinimalResource::collection($this->users),
            'last_message' => MessageResource::make($this->lastMessage),
            'unseens'      => $this->unSeensCount($pivot),
            //            'pinned_messages'    => MessageResource::collection($this->pinnedMessages()),


            //            'mentioned_messages' => MessageResource::collection($this->mentionedMessages($user)),
        ];
    }
}
