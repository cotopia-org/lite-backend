<?php

namespace App\Http\Resources;

use App\Models\Message;
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


        $chat_user = $this->users->find(auth()->user());
        return [
            'id'                 => $this->id,
            'title'              => $this->getTitle($user),
            'type'               => $this->type,
            'workspace_id'       => $this->workspace_id,
            'participants'       => UserSuperMinimalResource::collection($this->users),
            'last_message'       => MessageResource::make($this->lastMessage),
            'last_seen_message'  => MessageResource::make(Message::find($chat_user->pivot->last_message_seen_id)),
            'unseens'            => $this->messages_count,
            'mentioned_messages' => $this->mentions_count,
            'muted'              => $chat_user === NULL ? FALSE : $chat_user->pivot->muted,
            'created_at'         => $this->created_at->timestamp


        ];
    }
}
