<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->with(['children' => fn ($q2) => $q2
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with(['children' => fn ($q3) => $q3
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                    ])
                ])
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $breadcrumb = $this->buildBreadcrumb($category);

        return response()->json([
            'data'       => new CategoryResource($category),
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /** @return array<int, array{name: string, slug: string}> */
    private function buildBreadcrumb(Category $category): array
    {
        $trail = [];
        $current = $category;

        while ($current->parent_id) {
            $parent = Category::find($current->parent_id);

            if (! $parent) {
                break;
            }

            array_unshift($trail, ['name' => $parent->name, 'slug' => $parent->slug]);
            $current = $parent;
        }

        $trail[] = ['name' => $category->name, 'slug' => $category->slug];

        return $trail;
    }
}
