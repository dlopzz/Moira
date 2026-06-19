<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    public function myReviews(Request $request): JsonResponse
    {
        $reviews = Review::where('customer_id', $request->user()->id)
            ->with('product')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Review $review) => [
                'id'           => $review->id,
                'token'        => $review->token,
                'rating'       => $review->rating,
                'title'        => $review->title,
                'body'         => $review->body,
                'is_approved'  => $review->is_approved,
                'submitted_at' => $review->submitted_at?->toISOString(),
                'created_at'   => $review->created_at->toISOString(),
                'product'      => $review->product ? [
                    'id'    => $review->product->id,
                    'name'  => $review->product->name,
                    'slug'  => $review->product->slug,
                    'image' => collect($review->product->images ?? [])->first()
                        ? Storage::url(collect($review->product->images)->first())
                        : null,
                ] : null,
            ]);

        return response()->json(['data' => $reviews]);
    }

    public function show(string $token): JsonResponse
    {
        $review = Review::where('token', $token)
            ->whereNull('submitted_at')
            ->with(['product', 'customer'])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'token'         => $review->token,
                'product'       => [
                    'id'    => $review->product->id,
                    'name'  => $review->product->name,
                    'slug'  => $review->product->slug,
                    'image' => isset($review->product->images[0]) ? Storage::url($review->product->images[0]) : null,
                ],
                'customer_name' => $review->customer->name,
            ],
        ]);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        $review = Review::where('token', $token)
            ->whereNull('submitted_at')
            ->firstOrFail();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['nullable', 'string', 'max:255'],
            'body'   => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $review->update([
            ...$validated,
            'submitted_at' => now(),
            'is_approved'  => false,
        ]);

        return response()->json(['message' => 'Reseña enviada. ¡Gracias por tu opinión!']);
    }
}
