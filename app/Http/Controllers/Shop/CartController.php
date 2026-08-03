<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Cart\AddToCart;
use App\CheckoutSession;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Exceptions\InsufficientStockException;

final class CartController extends Controller
{
    public function index(): Response
    {
        $cart = resolve(CartSessionManager::class)->current();

        $cart?->load(['lines.purchasable.media']);

        $context = $cart
            ? resolve(CartManager::class)->calculate($cart)
            : null;

        return Inertia::render('shop/cart', [
            'cart' => $cart,
            'cartContext' => $context,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:'.shopper_table('products').',id'],
            'variant_id' => ['nullable', 'integer', 'exists:'.shopper_table('product_variants').',id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $product = Product::query()->scopes('publish')->findOrFail($data['product_id']);
        $variant = isset($data['variant_id'])
            ? ProductVariant::query()->where('product_id', $product->id)->findOrFail($data['variant_id'])
            : null;

        try {
            resolve(AddToCart::class)->handle($product, $variant, $data['quantity'] ?? 1);
        } catch (InsufficientStockException) {
            return back()->withErrors(['cart' => __('Insufficient stock available.')]);
        }

        $this->invalidateCheckoutSession();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product added to cart!')]);

        return back();
    }

    public function update(Request $request, int $line): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart) {
            return back();
        }

        resolve(CartManager::class)->update($cart, $line, ['quantity' => $data['quantity']]);

        $this->invalidateCheckoutSession();

        return back();
    }

    public function destroy(int $line): RedirectResponse
    {
        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart) {
            return back();
        }

        resolve(CartManager::class)->remove($cart, $line);

        $this->invalidateCheckoutSession();

        return back();
    }

    public function clear(): RedirectResponse
    {
        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart) {
            return back();
        }

        resolve(CartManager::class)->clear($cart);

        $this->invalidateCheckoutSession();

        return back();
    }

    private function invalidateCheckoutSession(): void
    {
        session()->forget([
            CheckoutSession::KEY,
            'stripe_payment',
            'stripe_order_number',
        ]);
    }
}
