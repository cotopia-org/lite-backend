<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'                  => $this->id,
            'availability_type'   => $this->availability_type,
            'user'                => UserMinimalResource::make($this->user),
            'days'                => $this->days,
            'start_time'          => $this->start_time,
            'end_time'            => $this->end_time,
            'is_recurrence'       => $this->is_recurrence,
            'recurrence_start_at' => $this->recurrence_start_at,
            'recurrence_end_at'   => $this->recurrence_end_at,
            'timezone'            => $this->timezone,
            'workspace_id'        => $this->workspace_id,
        ];
    }
}
