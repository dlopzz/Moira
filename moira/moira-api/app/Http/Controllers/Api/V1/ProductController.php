<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('categories')
            ->where('is_active', true);

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search): void {
                $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('sku ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('short_description ILIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        if ($request->filled('attributes')) {
            foreach ((array) $request->input('attributes') as $key => $value) {
                $query->whereHas('variants', function ($q) use ($key, $value): void {
                    $q->where('is_active', true)
                        ->whereRaw("attributes->>? = ?", [$key, $value]);
                });
            }
        }

        match ($request->input('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'newest' => $query->latest(),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->orderBy('name'),
        };

        $perPage = min((int) $request->input('per_page', 12), 48);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => ProductResource::collection($products),
            'meta' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function filters(Request $request): JsonResponse
    {
        $query = ProductVariant::query()
            ->where('is_active', true)
            ->whereNotNull('attributes')
            ->whereHas('product', fn ($q) => $q->where('is_active', true));

        if ($request->filled('category')) {
            $query->whereHas('product.categories', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->whereHas('product', fn ($q) => $q->whereRaw('name ILIKE ?', ["%{$search}%"])
                ->orWhereRaw('short_description ILIKE ?', ["%{$search}%"]));
        }

        $filters = [];
        $query->pluck('attributes')->each(function ($attrs) use (&$filters): void {
            foreach ($attrs as $key => $value) {
                $filters[$key][$value] = true;
            }
        });

        $result = (object) [];
        foreach ($filters as $key => $values) {
            $result->$key = array_keys($values);
        }

        return response()->json(['data' => $result]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::with('categories')
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with([
            'categories',
            'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'reviews' => fn ($q) => $q
                ->whereNotNull('submitted_at')
                ->where('is_approved', true)
                ->with('customer')
                ->latest('submitted_at'),
            'relatedProducts' => fn ($q) => $q->where('is_active', true)->limit(6),
        ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

}
