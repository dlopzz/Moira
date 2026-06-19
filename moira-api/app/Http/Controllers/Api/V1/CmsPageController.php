<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CmsPageResource;
use App\Models\CmsPage;
use Illuminate\Http\JsonResponse;

class CmsPageController extends Controller
{
    /** Footer pages — active + show_in_footer, ordered by sort_order */
    public function footer(): JsonResponse
    {
        $pages = CmsPage::where('is_active', true)
            ->where('show_in_footer', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'sort_order']);

        return response()->json(['data' => CmsPageResource::collection($pages)]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json(['data' => new CmsPageResource($page)]);
    }
}
