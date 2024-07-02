<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomListResource extends JsonResource
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
            'title'        => $this->title,
            'is_private'   => $this->is_private,
            'background'   => FileResource::make($this->background()),
            'logo'         => FileResource::make($this->logo()),
            'unseens'      => $this->unseens(auth()->user())
        ];
    }
}
