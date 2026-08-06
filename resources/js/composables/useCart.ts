import { router } from '@inertiajs/vue3';
import * as cart from '@/routes/shop/cart';

type AddPayload = {
    product_id: number;
    variant_id?: number | null;
    quantity?: number;
};

export function useCart() {
    function add(payload: AddPayload): void {
        router.post(cart.add.url(), payload, { preserveScroll: true });
    }

    function update(lineId: number, quantity: number): void {
        router.patch(
            cart.update.url({ line: lineId }),
            { quantity },
            { preserveScroll: true },
        );
    }

    function remove(lineId: number): void {
        router.delete(cart.destroy.url({ line: lineId }), {
            preserveScroll: true,
        });
    }

    function clear(): void {
        router.delete(cart.clear.url(), { preserveScroll: true });
    }

    return { add, update, remove, clear };
}
