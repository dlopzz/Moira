<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'title'    => $this->title,
            'subtitle' => $this->subtitle,
            'slug'     => $this->slug,
            'content'  => $this->when($this->content !== null, $this->content),
        ];
    }
}
