<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'is_draft' => $this->is_draft,
            'published_at' => $this->published_at ? $this->published_at->toIso8601String() : null,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
