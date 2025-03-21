<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id'        => $this->id,
            'path'      => $this->path,
            'url'       => $this->url,
            'mime_type' => $this->mime_type,
            'type'      => $this->type,
            //            'owner' => $this->fileable
        ];
    }
}
