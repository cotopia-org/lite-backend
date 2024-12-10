<?php

namespace App\Http\Resources;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {


        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'username'                => $this->username,
            'email'                   => $this->email,
            'token'                   => $this->token,
            'avatar'                  => FileResource::make($this->avatar),
            'active'                  => $this->active,
            'status'                  => $this->status,
            'bio'                     => $this->bio,
            'room_id'                 => $this->room_id,
            'voice_status'            => $this->voice_status,
            'video_status'            => $this->video_status,
            'coordinates'             => $this->coordinates,
            'screenshare_coordinates' => $this->screenshare_coordinates,
            'screenshare_size'        => $this->screenshare_size,
            'video_coordinates'       => $this->video_coordinates,
            'video_size'              => $this->video_size,
            'last_login'              => $this->updated_at,
            'is_bot'                  => $this->is_bot,
            'active_job'              => JobResource::make($this->activeJob()->first()),
            'active_contract'         => ContractResource::make($this->contracts->last()),
            'workspaces'              => $this->workspaces->map(function ($workspace) {
                return [
                    'title' => $workspace->title,
                    'id'    => $workspace->id,
                    'role'  => $workspace->pivot->role,
                ];
            }),
            'schedule_hours_in_week'  => $this->getScheduledHoursInWeek(),

        ];
    }
}
