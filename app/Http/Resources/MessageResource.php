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
            'user'       => UserMinimalResource::make($this->user),
            'text'       => $this->deleted_at === NULL ? $this->text : 'This message has been deleted',
            'files'      => FileResource::collection($this->files),
            'room_id'    => $this->room_id,
            'is_direct'  => $this->room->isDirectRoom(),
            'seen'       => $this->saw(auth()->user()),
            //TODO: has to have another req for seens
            'nonce_id'   => (int)$this->nonce_id,
            'is_edited'  => $this->is_edited,
            'is_pinned'  => $this->is_pinned,
            'reply_to'   => self::make($this->replyTo),
            'mentions'   => $this->mentions,
            'links'      => $this->links,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at?->timestamp,
            'deleted_at' => $this->deleted_at?->timestamp,

        ];
    }
}
