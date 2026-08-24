<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Product\AddProductReviewAction;
use App\Actions\Product\CustomerPurchasedProduct;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ProductReviewController extends Controller
{
    public function store(
        Request $request,
        Product $product,
        CustomerPurchasedProduct $purchased,
        AddProductReviewAction $addReview,
    ): RedirectResponse {
        abort_unless($product->isPublished(), 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:2000'],
            'is_recommended' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        if (! $purchased->handle($user, $product)) {
            return back()->withErrors([
                'review' => 'Hanya pembeli yang sudah menerima pesanan yang dapat menulis ulasan.',
            ]);
        }

        if ($purchased->alreadyReviewed($user, $product)) {
            return back()->withErrors([
                'review' => 'Kamu sudah menulis ulasan untuk produk ini.',
            ]);
        }

        $addReview->execute($product, [
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'is_recommended' => (bool) ($data['is_recommended'] ?? true),
        ], $user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Ulasan terkirim dan menunggu moderasi.',
        ]);

        return back();
    }
}
