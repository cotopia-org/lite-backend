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

            'old'        => $this->old,
            'mentions'   => MentionResource::collection($this->mentions),
            'members'    => $this->users->map(function ($user) use ($job, $period) {
                return [
                    'id'            => $user->id,
                    'total_minutes' => $job->getTime($user->id, $period),
                    'role'          => $user->pivot->role,
                    'status'        => $user->pivot->status,
                    'created_at'    => $user->pivot->created_at,
                ];
            }),
        ];
    }
}
