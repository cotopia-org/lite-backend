<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserJobResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $job = $this;
        $period = $request->period;
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->whenPivotLoaded('job_user', function () {
                return $this->pivot->status;
            }),

            'estimate'   => $this->estimate,
            'parent'     => self::make($this->parent),
            'level'      => $this->level,
            'created_at' => $this->whenPivotLoaded('job_user', function () {
                return $this->pivot->created_at;
            }),

            'old'           => $this->old,
            'mentions'      => MentionResource::collection($this->mentions),
            'total_minutes' => $this->whenPivotLoaded('job_user', function () use ($job, $period) {
                return $job->getTime($this->pivot->user_id, $period);
            }),
            'role' => $this->whenPivotLoaded('job_user', function ()  {
                return $this->pivot->role;
            }),

        ];
    }
}
