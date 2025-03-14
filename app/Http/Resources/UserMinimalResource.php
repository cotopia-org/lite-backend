<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMinimalResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'username'           => $this->username,
            'room_id'            => $this->room_id,
            'status'             => $this->status,
            'avatar'             => FileResource::make($this->avatar),
            'coordinates'        => $this->coordinates,
            'last_login'         => $this->updated_at,
            'verified'           => $this->verified ?? FALSE,
            'is_bot'             => $this->is_bot,
            'video_status'       => $this->video_status,
            'voice_status'       => $this->voice_status,
            'screenshare_status' => $this->screenshare_status,
            'hard_muted'         => $this->hard_muted,
            'created_at'         => $this->created_at->timestamp,
            'timeStarted'     => $this->timeStarted()


        ];
    }
}
