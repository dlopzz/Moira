<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description'      => $this->description,
            'image_url'        => $this->image ? Storage::disk('public')->url($this->image) : null,
            'meta_title'       => $this->meta_title ?? null,
            'meta_description' => $this->meta_description ?? null,
            'children'         => CategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
