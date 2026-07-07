<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'price' => (float) $this->price,
            'sale_price' => $this->sale_price ? (float) $this->sale_price : null,
            'stock' => $this->stock,
            'images' => collect($this->images ?? [])->map(fn (string $path) => Storage::url($path))->values()->all(),
            'product_type' => $this->product_type,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants
                ->where('is_active', true)
                ->values()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->price !== null ? (float) $v->price : null,
                    'stock' => $v->stock,
                    'attributes' => $v->attributes,
                    'image' => $v->image ? Storage::url($v->image) : null,
                    'label' => $v->label(),
                    'sort_order' => $v->sort_order,
                ])
            ),
            'reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'customer' => $r->customer->name ?? 'Anónimo',
                'submitted_at' => $r->submitted_at?->toISOString(),
            ])),
            'rating_average' => $this->whenLoaded('reviews', fn () => $this->reviews->isNotEmpty()
                ? round($this->reviews->avg('rating'), 1)
                : null
            ),
            'rating_count' => $this->whenLoaded('reviews', fn () => $this->reviews->count()),
            'related' => $this->whenLoaded('relatedProducts', fn () => $this->relatedProducts->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'sale_price' => $p->sale_price ? (float) $p->sale_price : null,
                'image' => collect($p->images ?? [])->first()
                    ? Storage::url(collect($p->images)->first())
                    : null,
            ])),
        ];
    }
}
