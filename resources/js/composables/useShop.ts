import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import * as zone from '@/routes/shop/zone';
import type { ShopSharedProps } from '@/types/shop';

/**
 * Accessor for the shared shop props injected by HandleInertiaRequests.
 *
 * Used by the storefront header, zone selector and cart badge.
 */
export function useShop() {
    const page = usePage<{ shop: ShopSharedProps }>();

    const shop = computed<ShopSharedProps>(() => page.props.shop);
    const cartCount = computed<number>(() => shop.value.cart_count);
    const currency = computed<string>(() => shop.value.currency);
    const currentZone = computed(() => shop.value.zone);
    const channels = computed(() => shop.value.channels);
    const availableZones = computed(() => shop.value.available_zones);
    const taxLabel = computed<string>(() => shop.value.tax_label);

    function changeZone(countryCode: string): void {
        router.patch(
            zone.update.url(),
            { country_code: countryCode },
            { preserveScroll: true, preserveState: true },
        );
    }

    return {
        shop,
        cartCount,
        currency,
        zone: currentZone,
        channels,
        availableZones,
        taxLabel,
        changeZone,
    };
}
