<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\CustomerCart;
use App\Support\CustomerCatalogPresenter;
use App\Support\CustomerCheckoutState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Cart\CartManager;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Exceptions\InvalidDiscountException;
use Shopper\Core\Enum\DiscountStatus;
use Shopper\Core\Models\Discount;
use Throwable;

final class CartController extends Controller
{
    public function __construct(
        private readonly CustomerCart $customerCart,
        private readonly CustomerCatalogPresenter $presenter,
        private readonly CustomerCheckoutState $checkoutState,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return $this->cartResponse($request);
    }

    public function add(Request $request): JsonResponse
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

        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);

        try {
            resolve(CartManager::class)->add($cart, $variant ?? $product, (int) ($data['quantity'] ?? 1));
        } catch (InsufficientStockException) {
            return response()->json(['message' => 'Stok tidak mencukupi.'], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $this->checkoutState->forget($user);

        return $this->cartResponse($request);
    }

    public function update(Request $request, int $line): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);

        try {
            resolve(CartManager::class)->update($cart, $line, ['quantity' => (int) $data['quantity']]);
        } catch (InsufficientStockException) {
            return response()->json(['message' => 'Stok tidak mencukupi.'], 422);
        }

        $this->checkoutState->forget($user);

        return $this->cartResponse($request);
    }

    public function destroy(Request $request, int $line): JsonResponse
    {
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);
        resolve(CartManager::class)->remove($cart, $line);
        $this->checkoutState->forget($user);

        return $this->cartResponse($request);
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);
        resolve(CartManager::class)->clear($cart);
        $this->checkoutState->forget($user);

        return $this->cartResponse($request);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);
        $code = strtoupper(trim($data['code']));
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);

        if ($cart->lines()->count() < 1) {
            return response()->json(['message' => 'Keranjang masih kosong.'], 422);
        }

        $discount = Discount::query()->where('code', $code)->first();
        if (! $discount instanceof Discount || ! $discount->is_active || $discount->status !== DiscountStatus::Active) {
            return response()->json(['message' => 'Kode kupon tidak valid.'], 422);
        }

        try {
            resolve(CartManager::class)->applyCoupon($cart, $code);
        } catch (InvalidDiscountException) {
            return response()->json(['message' => 'Kode kupon tidak valid.'], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Kupon tidak bisa dipakai.'], 422);
        }

        return $this->cartResponse($request);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user);
        resolve(CartManager::class)->removeCoupon($cart);

        return $this->cartResponse($request);
    }

    private function cartResponse(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user)->load(['lines.purchasable.media']);
        $context = $cart->lines->isEmpty()
            ? null
            : resolve(CartManager::class)->calculate($cart);

        return response()->json([
            'data' => $this->presenter->cart($cart, $context),
        ]);
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
