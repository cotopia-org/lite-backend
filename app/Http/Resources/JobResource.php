<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {


        $total_hours = $this->total_hours;
        if ($total_hours === NULL) {

            $user = $this->users()->first();
            if ($user !== NULL) {
                $total_hours = $this->getTime($user->id, $request->period)['sum_minutes'];

            }
        }
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'estimate'     => $this->estimate,
            'parent'       => self::make($this->parent),
            'level'        => $this->level,
            //            'children'     => JobResource::collection($this->jobs),
            //            'total_hours'  => $this->whenPivotLoaded('job_user', function () {
            //                return $this->getTime($this->pivot->user_id)['sum_hours'];
            //            }),
            'tags'         => TagMinimalResource::collection($this->tags),
            'total_hours'  => $total_hours,
            'created_at'   => $this->created_at,
            //            'members'      => UserMinimalResource::collection($users),
        ];
    }
}
