<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagMinimalResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspace_id,
            'title'        => $this->title,
            'created_at'   => $this->created_at
        ];
    }
}
