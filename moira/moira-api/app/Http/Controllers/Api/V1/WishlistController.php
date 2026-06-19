<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlist = Wishlist::firstOrCreate(['customer_id' => $request->user()->id]);
        $products = $wishlist->products()->with('categories')->get();

        return response()->json([
            'product_ids' => $products->pluck('id')->values()->all(),
            'data' => ProductResource::collection($products),
        ]);
    }

    public function ids(Request $request): JsonResponse
    {
        $wishlist = Wishlist::where('customer_id', $request->user()->id)->first();

        return response()->json([
            'product_ids' => $wishlist
                ? $wishlist->products()->pluck('products.id')->values()->all()
                : [],
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse
    {
        $wishlist = Wishlist::firstOrCreate(['customer_id' => $request->user()->id]);

        if ($wishlist->products()->where('product_id', $product->id)->exists()) {
            $wishlist->products()->detach($product->id);
            $inWishlist = false;
        } else {
            $wishlist->products()->attach($product->id);
            $inWishlist = true;
        }

        return response()->json([
            'in_wishlist' => $inWishlist,
            'product_id' => $product->id,
        ]);
    }
}
