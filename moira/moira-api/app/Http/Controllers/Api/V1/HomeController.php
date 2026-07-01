<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = HomeSection::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $result = $sections->map(fn (HomeSection $s) => $this->resolveSection($s));

        return response()->json(['data' => $result]);
    }

    private function resolveSection(HomeSection $section): array
    {
        $settings = $section->settings ?? [];

        if ($section->type === 'hero_slider') {
            $settings['slides'] = array_map(function (array $slide) {
                if (! empty($slide['image']) && ! str_starts_with($slide['image'], 'http')) {
                    $slide['image'] = Storage::url($slide['image']);
                }
                return $slide;
            }, $settings['slides'] ?? []);
        }

        if ($section->type === 'product_tabs') {
            $perTab = (int) ($settings['per_tab'] ?? 8);
            $settings['tabs'] = array_map(function (array $tab) use ($perTab) {
                $products = $this->resolveTabProducts($tab, $perTab);
                return array_merge($tab, [
                    'products' => ProductResource::collection($products)->resolve(),
                ]);
            }, $settings['tabs'] ?? []);
        }

        if ($section->type === 'banner') {
            if (! empty($settings['image']) && ! str_starts_with($settings['image'], 'http')) {
                $settings['image'] = Storage::url($settings['image']);
            }
        }

        return [
            'id'         => $section->id,
            'type'       => $section->type,
            'title'      => $section->title,
            'sort_order' => $section->sort_order,
            'settings'   => $settings,
        ];
    }

    private function resolveTabProducts(array $tab, int $limit): Collection
    {
        return match ($tab['type'] ?? '') {
            'latest' => Product::with('categories')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(),

            'on_sale' => Product::with('categories')
                ->where('is_active', true)
                ->whereNotNull('sale_price')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(),

            'best_sellers' => $this->bestSellers($limit),

            'by_category' => Product::with('categories')
                ->where('is_active', true)
                ->whereHas('categories', fn ($q) => $q->where('slug', $tab['category_slug'] ?? ''))
                ->limit($limit)
                ->get(),

            'custom' => Product::with('categories')
                ->whereIn('id', $tab['product_ids'] ?? [])
                ->where('is_active', true)
                ->get(),

            default => collect(),
        };
    }

    private function bestSellers(int $limit): Collection
    {
        $ids = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->pluck('product_id');

        if ($ids->isEmpty()) {
            // Fallback: most recently created products
            return Product::with('categories')
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return Product::with('categories')
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($p) => $ids->search($p->id))
            ->values();
    }
}
