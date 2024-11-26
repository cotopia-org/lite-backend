<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {

        return [
            'id'         => $this->id,
            'user'       => $this->user_id,
            'text'       => $this->deleted_at === NULL ? $this->text : 'This message has been deleted',
            'files'      => FileResource::collection($this->files),
            'chat_id'    => $this->chat_id,
            'seen'       => $this->saw(auth()->user()),
            'nonce_id'   => (int)$this->nonce_id,
            'is_edited'  => $this->is_edited,
            'is_pinned'  => $this->is_pinned,
            'reply_to'   => self::make($this->replyTo),
            'mentions'   => MentionResource::collection($this->mentions),
            'links'      => $this->links,
            'reacts'     => $this->reacts,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at?->timestamp,
            'deleted_at' => $this->deleted_at?->timestamp,

        ];
    }
}
