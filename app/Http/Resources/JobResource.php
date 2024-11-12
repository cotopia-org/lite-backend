<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'estimate'     => $this->estimate,
            //            'total_hours'  => $this->whenPivotLoaded('job_user', function () {
            //                return $this->getTime($this->pivot->user_id)['sum_hours'];
            //            }),
            'total_hours'  => $this->total_hours
            //            'members'      => UserMinimalResource::collection($users),
        ];
    }
}
