<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, ChevronRight, Lock, ShoppingBag } from 'lucide-vue-next';
import { computed, defineAsyncComponent, ref, watch } from 'vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Card from '@/components/shop/card.vue';
import Container from '@/components/shop/container.vue';
import CouponField from '@/components/shop/coupon-field.vue';
import KomercePaymentPanel from '@/components/shop/komerce-payment-panel.vue';
import type { KomercePaymentInstructions } from '@/components/shop/komerce-payment-panel.vue';
import ShipmentRatePicker from '@/components/shop/shipment-rate-picker.vue';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';
import { cart as cartRoute } from '@/routes/shop';
import * as checkout from '@/routes/shop/checkout';
import type { Address, Cart, CartContext, DeliveryOption } from '@/types/shop';

const StripePaymentForm = defineAsyncComponent(
    () => import('@/components/shop/stripe-payment-form.vue'),
);

type ShipmentPackage = {
    inventory_id: number;
    inventory_name: string;
    lines: Array<{
        purchasable_type: string;
        purchasable_id: number;
        qty: number;
    }>;
};

type ShippingAddressForm = {
    first_name: string;
    last_name: string;
    street_address: string;
    street_address_plus: string;
    postal_code: string;
    city: string;
    state: string;
    phone_number: string;
    rajaongkir_destination_id: string;
    rajaongkir_destination_label: string;
};

type DestinationResult = {
    id: string;
    label: string;
    province_name: string | null;
    city_name: string | null;
    district_name: string | null;
    subdistrict_name: string | null;
    zip_code: string | null;
};

const props = defineProps<{
    cart: Cart;
    cartContext: CartContext;
    savedAddresses: Address[];
    shippingAddress: ShippingAddressForm | null;
    deliveryOptions: DeliveryOption[];
    selectedDeliveryOption: string | number | null;
    allocation: ShipmentPackage[] | null;
    deliveryOptionsByShipment: Record<number | string, DeliveryOption[]>;
    selectedRatesByShipment: Record<number | string, string>;
    paymentOptions: Array<{
        id: number;
        title: string;
        driver: string;
        logo?: string | null;
        channel_code?: string | null;
        payment_type?: string | null;
    }>;
    selectedPaymentMethod: number | null;
    step: 1 | 2 | 3;
    stripeData: {
        client_secret: string;
        publishable_key: string;
        return_url: string;
    } | null;
    komercePayment: KomercePaymentInstructions | null;
    komerceEnabled: boolean;
    shippingRatesHint?: string | null;
    couponCode?: string | null;
}>();

const { currency, taxLabel, zone } = useShop();

const step = computed<1 | 2 | 3>(() => props.step);

const maxStep = computed<1 | 2 | 3>(() => {
    if (props.selectedDeliveryOption !== null) {
        return 3;
    }

    if (props.shippingAddress) {
        return 2;
    }

    return 1;
});

const selectedAddressId = ref<number | null>(null);

const addressForm = useForm<ShippingAddressForm>({
    first_name: props.shippingAddress?.first_name ?? '',
    last_name: props.shippingAddress?.last_name ?? '',
    street_address: props.shippingAddress?.street_address ?? '',
    street_address_plus: props.shippingAddress?.street_address_plus ?? '',
    postal_code: props.shippingAddress?.postal_code ?? '',
    city: props.shippingAddress?.city ?? '',
    state: props.shippingAddress?.state ?? '',
    phone_number: props.shippingAddress?.phone_number ?? '',
    rajaongkir_destination_id:
        props.shippingAddress?.rajaongkir_destination_id ?? '',
    rajaongkir_destination_label:
        props.shippingAddress?.rajaongkir_destination_label ?? '',
});

const destinationQuery = ref(
    props.shippingAddress?.rajaongkir_destination_label ?? '',
);
const destinationResults = ref<DestinationResult[]>([]);
const destinationSearching = ref(false);
const destinationSearchError = ref<string | null>(null);
let destinationSearchTimer: ReturnType<typeof setTimeout> | null = null;

watch(destinationQuery, (value) => {
    if (destinationSearchTimer) {
        clearTimeout(destinationSearchTimer);
    }

    if (!props.komerceEnabled || value.trim().length < 2) {
        destinationResults.value = [];
        destinationSearchError.value = null;
        return;
    }

    // Keep selected label when it matches the selected id's label.
    if (
        addressForm.rajaongkir_destination_id &&
        value === addressForm.rajaongkir_destination_label
    ) {
        return;
    }

    destinationSearchTimer = setTimeout(() => {
        void searchDestinations(value.trim());
    }, 300);
});

async function searchDestinations(query: string): Promise<void> {
    destinationSearching.value = true;
    destinationSearchError.value = null;

    try {
        const response = await fetch(
            `/checkout/destinations?q=${encodeURIComponent(query)}&limit=10`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error('Destination search failed');
        }

        const payload = (await response.json()) as {
            data?: DestinationResult[];
        };
        destinationResults.value = Array.isArray(payload.data)
            ? payload.data
            : [];
    } catch {
        destinationResults.value = [];
        destinationSearchError.value =
            'Tidak dapat mencari tujuan saat ini.';
    } finally {
        destinationSearching.value = false;
    }
}

function selectDestination(result: DestinationResult): void {
    addressForm.rajaongkir_destination_id = String(result.id);
    addressForm.rajaongkir_destination_label = result.label;
    destinationQuery.value = result.label;
    destinationResults.value = [];
    addressForm.clearErrors(
        'rajaongkir_destination_id',
        'postal_code',
        'city',
    );

    // Always sync city/zip from RajaOngkir so typed placeholders don't
    // leave the form looking filled while Inertia still posts blanks.
    if (result.city_name) {
        addressForm.city = result.city_name;
    }

    if (result.zip_code) {
        addressForm.postal_code = result.zip_code;
    }
}

function clearDestination(): void {
    addressForm.rajaongkir_destination_id = '';
    addressForm.rajaongkir_destination_label = '';
    destinationQuery.value = '';
    destinationResults.value = [];
}

const shippingForm = useForm<{ service_code: string }>({
    service_code:
        props.selectedDeliveryOption !== null
            ? String(props.selectedDeliveryOption)
            : '',
});

const isMultiPackage = computed<boolean>(
    () => (props.allocation?.length ?? 0) > 1,
);

const ratesByShipment = ref<Record<number | string, string>>(
    { ...props.selectedRatesByShipment },
);

const multiShippingTotal = computed<number>(() => {
    let sum = 0;
    for (const pkg of props.allocation ?? []) {
        const code = ratesByShipment.value[pkg.inventory_id];
        if (!code) continue;
        const options = props.deliveryOptionsByShipment[pkg.inventory_id] ?? [];
        const opt = options.find((o) => String(o.service_code) === String(code));
        if (opt) sum += opt.amount;
    }
    return sum;
});

const multiShippingCurrency = computed<string>(() => {
    for (const pkg of props.allocation ?? []) {
        const code = ratesByShipment.value[pkg.inventory_id];
        if (!code) continue;
        const options = props.deliveryOptionsByShipment[pkg.inventory_id] ?? [];
        const opt = options.find((o) => String(o.service_code) === String(code));
        if (opt?.currency) return opt.currency;
    }
    return 'IDR';
});

const allPackagesSelected = computed<boolean>(() =>
    (props.allocation ?? []).every((pkg) =>
        Boolean(ratesByShipment.value[pkg.inventory_id]),
    ),
);

function submitMultiShipping(): void {
    router.post(
        checkout.shippingOption.url(),
        { rates: ratesByShipment.value },
        { preserveScroll: true },
    );
}

const paymentForm = useForm<{ payment_method_id: number | null }>({
    payment_method_id: props.selectedPaymentMethod ?? null,
});

const selectedDelivery = computed<DeliveryOption | null>(
    () =>
        props.deliveryOptions.find(
            (o) => o.service_code === props.selectedDeliveryOption,
        ) ?? null,
);

const currentPaymentMethod = computed(
    () =>
        props.paymentOptions.find(
            (m) => m.id === paymentForm.payment_method_id,
        ) ?? null,
);

const isStripeSelected = computed<boolean>(
    () => currentPaymentMethod.value?.driver === 'stripe',
);
const isKomerceSelected = computed<boolean>(
    () => currentPaymentMethod.value?.driver === 'komerce',
);
const canPlaceOrder = computed<boolean>(
    () =>
        Boolean(currentPaymentMethod.value) &&
        !isStripeSelected.value &&
        (!isKomerceSelected.value || !props.komercePayment),
);
const preparingStripe = ref<boolean>(false);
const stripeMounted = ref<boolean>(false);

watch(
    () => isStripeSelected.value && Boolean(props.stripeData),
    (active) => {
        if (active) {
            stripeMounted.value = true;
        }
    },
    { immediate: true },
);

watch(
    () => paymentForm.payment_method_id,
    (id) => {
        if (!id) {
            return;
        }

        const method = props.paymentOptions.find((m) => m.id === id) ?? null;

        if (!method) {
            return;
        }

        if (method.driver === 'stripe' && !props.stripeData) {
            preparingStripe.value = true;
            router.post(
                checkout.preparePayment.url(),
                { payment_method_id: id },
                {
                    preserveScroll: true,
                    onFinish: () => (preparingStripe.value = false),
                },
            );
        }
    },
);

const total = computed<number>(() => {
    const sub = props.cartContext?.total ?? 0;
    const delivery = isMultiPackage.value
        ? multiShippingTotal.value
        : (selectedDelivery.value?.amount ?? 0);

    return sub + delivery;
});

function selectAddress(address: Address): void {
    selectedAddressId.value = address.id;
    addressForm.first_name = address.first_name ?? '';
    addressForm.last_name = address.last_name;
    addressForm.street_address = address.street_address;
    addressForm.street_address_plus = address.street_address_plus ?? '';
    addressForm.postal_code = address.postal_code;
    addressForm.city = address.city;
    addressForm.state = address.state ?? '';
    addressForm.phone_number = address.phone_number ?? '';
    // Destinasi RajaOngkir harus dipilih ulang agar ongkir akurat.
    addressForm.rajaongkir_destination_id = '';
    addressForm.rajaongkir_destination_label = '';
    destinationQuery.value = [address.city, address.postal_code]
        .filter(Boolean)
        .join(' ');
    destinationResults.value = [];
    if (destinationQuery.value.trim().length >= 2) {
        searchDestinations(destinationQuery.value.trim());
    }
}

function clearAddress(): void {
    selectedAddressId.value = null;
    addressForm.reset();
}

function goToStep(target: 1 | 2 | 3): void {
    if (target === step.value) {
        return;
    }

    if (target > maxStep.value) {
        return;
    }

    router.get(
        checkout.index.url(),
        { step: target },
        { preserveScroll: true, preserveState: false },
    );
}

function submitAddress(): void {
    if (
        props.komerceEnabled &&
        !String(addressForm.rajaongkir_destination_id ?? '').trim()
    ) {
        addressForm.setError(
            'rajaongkir_destination_id',
            'Pilih kecamatan dari daftar pencarian.',
        );

        return;
    }

    addressForm
        .transform((data) => ({
            ...data,
            rajaongkir_destination_id: String(
                data.rajaongkir_destination_id ?? '',
            ).trim(),
            rajaongkir_destination_label: String(
                data.rajaongkir_destination_label ?? '',
            ).trim(),
            postal_code: String(data.postal_code ?? '').trim(),
            city: String(data.city ?? '').trim(),
        }))
        .post(checkout.shippingAddress.url(), { preserveScroll: true });
}

function submitShipping(): void {
    shippingForm.post(checkout.shippingOption.url(), { preserveScroll: true });
}

function placeOrder(): void {
    paymentForm.post(checkout.placeOrder.url(), { preserveScroll: true });
}

function lineImage(line: Cart['lines'][number]): string | null {
    return (
        line.purchasable.thumbnail ?? line.purchasable.images?.[0]?.url ?? null
    );
}

function lineName(line: Cart['lines'][number]): string {
    return (line.purchasable as { name?: string })?.name ?? '';
}

const steps = [
    { n: 1, label: 'Alamat' },
    { n: 2, label: 'Ongkir' },
    { n: 3, label: 'Pembayaran' },
] as const;
</script>

<template>
    <Head title="Checkout" />

    <AppPageHeader
        class="lg:hidden"
        title="Checkout"
        :back-href="cartRoute.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="py-8 sm:py-12">
        <h1 class="om-page-title hidden !text-lg lg:block">Checkout</h1>

        <nav class="mt-8 mb-10">
            <ol class="flex items-center gap-2">
                <li
                    v-for="(s, i) in steps"
                    :key="s.n"
                    class="flex items-center gap-2"
                >
                    <button
                        type="button"
                        :disabled="s.n > maxStep"
                        :class="[
                            'flex items-center gap-2 text-sm font-medium transition',
                            step === s.n
                                ? 'text-[var(--om-navy)]'
                                : maxStep > s.n
                                  ? 'text-green-600'
                                  : 'text-zinc-400',
                        ]"
                        @click="goToStep(s.n as 1 | 2 | 3)"
                    >
                        <span
                            :class="[
                                'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                                step === s.n
                                    ? 'bg-[var(--om-navy)] text-white'
                                    : step > s.n
                                      ? 'bg-green-100 text-green-600'
                                      : 'bg-zinc-100 text-zinc-400',
                            ]"
                        >
                            <Check
                                v-if="step > s.n"
                                class="size-4"
                                aria-hidden="true"
                            />
                            <template v-else>{{ s.n }}</template>
                        </span>
                        {{ s.label }}
                    </button>
                    <ChevronRight
                        v-if="i < steps.length - 1"
                        class="size-4 text-zinc-300"
                        aria-hidden="true"
                    />
                </li>
            </ol>
        </nav>

        <div class="lg:grid lg:grid-cols-12 lg:gap-x-12">
            <div class="lg:col-span-7">
                <template v-if="step === 1">
                    <div v-if="savedAddresses.length" class="mb-8">
                        <h2 class="text-[13px] font-semibold text-zinc-900">
                            Alamat tersimpan
                        </h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="address in savedAddresses"
                                :key="address.id"
                                type="button"
                                :class="[
                                    'rounded-md border text-left transition',
                                    selectedAddressId === address.id
                                        ? 'border-[var(--om-navy)] ring-2 ring-[var(--om-navy)]'
                                        : 'border-zinc-200 hover:border-zinc-400',
                                ]"
                                @click="selectAddress(address)"
                            >
                                <Card>
                                    <p
                                        class="text-sm font-medium text-zinc-900"
                                    >
                                        {{ address.first_name }}
                                        {{ address.last_name }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500">
                                        {{ address.street_address }},
                                        {{ address.city }}
                                        {{ address.postal_code }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        {{ address.country?.name }}
                                    </p>
                                    <span
                                        v-if="address.shipping_default"
                                        class="mt-2 inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700"
                                    >
                                        Utama
                                    </span>
                                </Card>
                            </button>
                        </div>

                        <button
                            v-if="selectedAddressId"
                            type="button"
                            class="mt-3 text-sm text-zinc-500 underline transition hover:text-zinc-900"
                            @click="clearAddress"
                        >
                            Pakai alamat lain
                        </button>

                        <hr class="my-6 border-zinc-200" />
                    </div>

                    <form class="space-y-5" @submit.prevent="submitAddress">
                        <h2 class="text-[13px] font-semibold text-zinc-900">
                            Alamat pengiriman
                        </h2>

                        <div class="grid grid-cols-2 gap-4">
                            <AuthTextField
                                id="first_name"
                                v-model="addressForm.first_name"
                                label="Nama depan"
                                placeholder="Nama depan"
                                :error="addressForm.errors.first_name"
                            />
                            <AuthTextField
                                id="last_name"
                                v-model="addressForm.last_name"
                                label="Nama belakang"
                                placeholder="Nama belakang"
                                :error="addressForm.errors.last_name"
                            />
                        </div>

                        <AuthTextField
                            id="street_address"
                            v-model="addressForm.street_address"
                            label="Alamat"
                            placeholder="Jl. contoh no. 1"
                            :error="addressForm.errors.street_address"
                        />

                        <AuthTextField
                            id="street_address_plus"
                            v-model="addressForm.street_address_plus"
                            label="Apartemen, blok, dll. (opsional)"
                            placeholder="Blok / unit (opsional)"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <AuthTextField
                                id="city"
                                v-model="addressForm.city"
                                label="Kota"
                                placeholder="Kota"
                                :error="addressForm.errors.city"
                            />
                            <AuthTextField
                                id="postal_code"
                                v-model="addressForm.postal_code"
                                label="Kode pos"
                                placeholder="Kode pos"
                                :error="addressForm.errors.postal_code"
                            />
                            <AuthTextField
                                id="state"
                                v-model="addressForm.state"
                                label="Provinsi"
                                placeholder="Provinsi"
                                :error="addressForm.errors.state"
                            />
                            <AuthTextField
                                id="country"
                                :model-value="zone?.country_name ?? ''"
                                label="Negara"
                                readonly
                            />
                        </div>

                        <AuthTextField
                            id="phone_number"
                            v-model="addressForm.phone_number"
                            label="Telepon (opsional)"
                            type="tel"
                            placeholder="08…"
                        />

                        <div class="space-y-1.5">
                            <label for="destination_search" class="om-label">
                                Kecamatan pengiriman
                                <span v-if="komerceEnabled" class="text-red-600"
                                    >*</span
                                >
                            </label>
                            <p class="text-[11px] leading-snug text-zinc-500">
                                Ketik nama kecamatan / kota, lalu pilih dari
                                daftar supaya ongkir akurat.
                            </p>
                            <div class="relative">
                                <input
                                    id="destination_search"
                                    v-model="destinationQuery"
                                    type="search"
                                    autocomplete="off"
                                    class="om-control w-full border border-zinc-200 bg-white px-3 text-zinc-900 outline-none placeholder:text-zinc-400 focus:border-[var(--om-navy)]"
                                    :placeholder="
                                        komerceEnabled
                                            ? 'Contoh: Kedawung Cirebon'
                                            : 'Opsional saat Komerce dinonaktifkan'
                                    "
                                    @focus="
                                        destinationQuery.trim().length >= 2 &&
                                        searchDestinations(
                                            destinationQuery.trim(),
                                        )
                                    "
                                />
                                <button
                                    v-if="addressForm.rajaongkir_destination_id"
                                    type="button"
                                    class="absolute inset-y-0 right-2 text-xs text-zinc-500 hover:text-zinc-800"
                                    @click="clearDestination"
                                >
                                    Ganti
                                </button>
                                <div
                                    v-if="destinationResults.length"
                                    class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border border-zinc-200 bg-white shadow-sm"
                                >
                                    <button
                                        v-for="result in destinationResults"
                                        :key="result.id"
                                        type="button"
                                        class="block w-full px-3 py-2.5 text-left text-[13px] hover:bg-zinc-50"
                                        @click="selectDestination(result)"
                                    >
                                        {{ result.label }}
                                    </button>
                                </div>
                            </div>
                            <p
                                v-if="destinationSearching"
                                class="text-xs text-zinc-500"
                            >
                                Mencari…
                            </p>
                            <p
                                v-else-if="destinationSearchError"
                                class="text-xs text-red-600"
                            >
                                {{ destinationSearchError }}
                            </p>
                            <p
                                v-else-if="
                                    addressForm.rajaongkir_destination_id
                                "
                                class="rounded-md bg-emerald-50 px-2.5 py-1.5 text-[12px] text-emerald-800"
                            >
                                ✓
                                {{
                                    addressForm.rajaongkir_destination_label ||
                                    destinationQuery
                                }}
                            </p>
                            <p
                                v-else-if="komerceEnabled"
                                class="text-xs text-amber-700"
                            >
                                Wajib pilih dari daftar pencarian (jangan ketik
                                manual saja).
                            </p>
                            <p
                                v-if="
                                    addressForm.errors
                                        .rajaongkir_destination_id
                                "
                                class="text-xs text-red-600"
                            >
                                {{
                                    addressForm.errors
                                        .rajaongkir_destination_id
                                }}
                            </p>
                        </div>

                        <div class="flex">
                            <button
                                type="submit"
                                class="om-btn-primary inline-flex items-center justify-center px-5 disabled:opacity-50"
                                :disabled="
                                    addressForm.processing ||
                                    (komerceEnabled &&
                                        !addressForm.rajaongkir_destination_id)
                                "
                            >
                                Lanjut ke pengiriman
                            </button>
                        </div>
                    </form>
                </template>

                <template v-else-if="step === 2">
                    <template v-if="isMultiPackage">
                        <div class="space-y-5">
                            <h2 class="text-[13px] font-semibold text-zinc-900">
                                Metode pengiriman
                            </h2>

                            <div
                                v-if="shippingRatesHint"
                                class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                            >
                                {{ shippingRatesHint }}
                            </div>

                            <ShipmentRatePicker
                                v-model="ratesByShipment"
                                :packages="allocation!"
                                :delivery-options-by-shipment="
                                    deliveryOptionsByShipment
                                "
                                :empty-hint="shippingRatesHint"
                            />

                            <div class="flex">
                                <button
                                    type="button"
                                    class="om-btn-primary inline-flex items-center justify-center px-5 disabled:opacity-50"
                                    :disabled="!allPackagesSelected"
                                    @click="submitMultiShipping"
                                >
                                    Lanjut ke pembayaran
                                </button>
                            </div>
                        </div>
                    </template>

                    <template v-else>
                        <div v-if="!deliveryOptions.length">
                            <div
                                class="flex items-center gap-4 rounded-md border border-amber-200 bg-amber-50 p-4"
                            >
                                <ShoppingBag
                                    class="size-5 text-amber-600"
                                    aria-hidden="true"
                                />
                                <p class="text-sm text-amber-900">
                                    {{
                                        shippingRatesHint ||
                                        'Tidak ada opsi pengiriman untuk alamatmu.'
                                    }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="mt-4 text-sm text-zinc-500 transition hover:text-zinc-900"
                                @click="goToStep(1)"
                            >
                                ← Kembali ke alamat
                            </button>
                        </div>

                        <form
                            v-else
                            class="space-y-5"
                            @submit.prevent="submitShipping"
                        >
                            <h2 class="text-[13px] font-semibold text-zinc-900">
                                Metode pengiriman
                            </h2>
                            <p
                                v-if="shippingForm.errors.service_code"
                                class="text-xs text-red-600"
                            >
                                {{ shippingForm.errors.service_code }}
                            </p>

                            <div class="flex flex-col gap-3">
                                <label
                                    v-for="option in deliveryOptions"
                                    :key="option.service_code"
                                    :class="[
                                        'flex cursor-pointer items-center justify-between gap-4 rounded-md border p-4 transition',
                                        shippingForm.service_code ===
                                        option.service_code
                                            ? 'border-[var(--om-navy)] ring-2 ring-[var(--om-navy)]'
                                            : 'border-zinc-200 hover:border-zinc-300',
                                    ]"
                                >
                                    <input
                                        v-model="shippingForm.service_code"
                                        type="radio"
                                        :value="option.service_code"
                                        name="service_code"
                                        class="sr-only"
                                    />
                                    <div class="flex items-start gap-3">
                                        <img
                                            v-if="option.carrier_logo"
                                            :src="option.carrier_logo"
                                            :alt="option.carrier_name ?? ''"
                                            class="mt-0.5 size-6 rounded-full object-cover"
                                        />
                                        <div class="flex flex-col">
                                            <span
                                                class="font-heading text-sm font-medium text-zinc-900"
                                                >{{
                                                    option.service_name
                                                }}</span
                                            >
                                            <span
                                                v-if="option.estimated_days"
                                                class="text-sm text-zinc-500"
                                                >{{
                                                    option.estimated_days
                                                }}
                                                hari pengiriman</span
                                            >
                                            <span
                                                v-else-if="option.description"
                                                class="text-sm text-zinc-500"
                                                >{{
                                                    option.description
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <span
                                        class="text-sm font-medium text-zinc-900"
                                        >{{
                                            formatMoney(
                                                option.amount,
                                                option.currency,
                                            )
                                        }}</span
                                    >
                                </label>
                            </div>

                            <div class="flex">
                                <button
                                    type="submit"
                                    class="om-btn-primary inline-flex items-center justify-center px-5 disabled:opacity-50"
                                    :disabled="
                                        !shippingForm.service_code ||
                                        shippingForm.processing
                                    "
                                >
                                    Lanjut ke pembayaran
                                </button>
                            </div>
                        </form>
                    </template>
                </template>

                <template v-else>
                    <div class="space-y-5">
                        <div>
                            <h2 class="text-[13px] font-semibold text-zinc-900">
                                Metode pembayaran
                            </h2>
                            <p class="text-sm text-zinc-500">
                                Semua transaksi aman dan terenkripsi.
                            </p>
                        </div>

                        <p
                            v-if="paymentForm.errors.payment_method_id"
                            class="text-xs text-red-600"
                        >
                            {{ paymentForm.errors.payment_method_id }}
                        </p>

                        <p
                            v-if="!paymentOptions.length"
                            class="text-sm text-zinc-600"
                        >
                            Tidak ada metode pembayaran untuk wilayahmu.
                        </p>

                        <template v-else>
                            <div
                                class="flex flex-col gap-1 rounded-md border border-zinc-200 p-1"
                            >
                                <label
                                    v-for="method in paymentOptions"
                                    :key="method.id"
                                    :class="[
                                        'group flex cursor-pointer items-center justify-between gap-6 rounded-md px-3 py-3 transition',
                                        paymentForm.payment_method_id ===
                                        method.id
                                            ? 'bg-zinc-100'
                                            : 'hover:bg-zinc-50',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="[
                                                'inline-flex size-4 items-center justify-center rounded-full border-2 transition',
                                                paymentForm.payment_method_id ===
                                                method.id
                                                    ? 'border-[var(--om-navy)]'
                                                    : 'border-zinc-300',
                                            ]"
                                        >
                                            <span
                                                v-if="
                                                    paymentForm.payment_method_id ===
                                                    method.id
                                                "
                                                class="size-2 rounded-full bg-[var(--om-navy)]"
                                            />
                                        </span>
                                        <input
                                            v-model="
                                                paymentForm.payment_method_id
                                            "
                                            type="radio"
                                            :value="method.id"
                                            name="payment_method_id"
                                            class="sr-only"
                                        />
                                        <span
                                            class="text-sm font-medium text-zinc-900"
                                            >{{ method.title }}</span
                                        >
                                    </div>
                                    <img
                                        v-if="method.logo"
                                        :src="method.logo!"
                                        :alt="method.title"
                                        class="h-5 w-auto object-cover"
                                    />
                                </label>
                            </div>

                            <div
                                v-if="isStripeSelected && stripeData"
                                class="space-y-4 pt-2"
                            >
                                <div class="flex items-center gap-3">
                                    <h3
                                        class="text-xs font-medium tracking-wider text-zinc-500 uppercase"
                                    >
                                        Detail kartu
                                    </h3>
                                    <span class="h-px flex-1 bg-zinc-200" />
                                </div>

                                <StripePaymentForm
                                    v-if="stripeMounted && stripeData"
                                    :key="stripeData.client_secret"
                                    :client-secret="stripeData.client_secret"
                                    :publishable-key="
                                        stripeData.publishable_key
                                    "
                                    :return-url="stripeData.return_url"
                                    :total="total"
                                />
                            </div>

                            <div
                                v-if="isKomerceSelected && komercePayment"
                                class="pt-2"
                            >
                                <KomercePaymentPanel
                                    :payment="komercePayment"
                                />
                            </div>

                            <template v-if="canPlaceOrder">
                                <div
                                    class="flex flex-col gap-3 border-t border-zinc-200 pt-5"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div class="flex flex-col">
                                            <span class="text-xs text-zinc-500"
                                                >Total {{ taxLabel }}</span
                                            >
                                            <span
                                                class="text-lg font-semibold text-zinc-900"
                                                >{{
                                                    formatMoney(total, currency)
                                                }}</span
                                            >
                                        </div>
                                        <button
                                            type="button"
                                            class="om-btn-primary inline-flex items-center justify-center px-5 disabled:opacity-50"
                                            :disabled="paymentForm.processing"
                                            @click="placeOrder"
                                        >
                                            {{
                                                paymentForm.processing
                                                    ? 'Memproses…'
                                                    : isKomerceSelected
                                                      ? 'Bayar sekarang'
                                                      : 'Buat pesanan'
                                            }}
                                        </button>
                                    </div>
                                    <p
                                        class="inline-flex items-center gap-1.5 text-xs text-zinc-500"
                                    >
                                        <Lock
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        Aman &amp; terenkripsi
                                    </p>
                                </div>
                            </template>

                            <div
                                v-if="
                                    isStripeSelected &&
                                    !stripeData &&
                                    preparingStripe
                                "
                                class="flex items-center gap-2 pt-3 text-sm text-zinc-500"
                            >
                                <span
                                    class="inline-block size-4 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-900"
                                />
                                Menyiapkan formulir pembayaran…
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <Card class="rounded-md border border-zinc-200 p-6">
                    <h2 class="text-[13px] font-semibold text-zinc-900">
                        Ringkasan pesanan
                    </h2>

                    <ul
                        v-if="cart"
                        role="list"
                        class="mt-4 divide-y divide-zinc-200"
                    >
                        <li
                            v-for="line in cart.lines"
                            :key="line.id"
                            class="flex gap-3 py-3"
                        >
                            <div
                                class="size-14 shrink-0 overflow-hidden rounded-md bg-zinc-100"
                            >
                                <img
                                    v-if="lineImage(line)"
                                    :src="lineImage(line)!"
                                    :alt="lineName(line)"
                                    class="size-full object-cover"
                                />
                            </div>
                            <div class="flex flex-1 justify-between">
                                <div>
                                    <p
                                        class="text-sm font-medium text-zinc-900"
                                    >
                                        {{ lineName(line) }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        Jml: {{ line.quantity }}
                                    </p>
                                </div>
                                <p
                                    class="text-sm font-medium text-zinc-900"
                                >
                                    {{
                                        formatMoney(
                                            line.unit_price_amount *
                                                line.quantity,
                                            currency,
                                        )
                                    }}
                                </p>
                            </div>
                        </li>
                    </ul>

                    <dl
                        class="mt-4 space-y-3 border-t border-zinc-200 pt-4 text-sm text-zinc-500"
                    >
                        <div class="border-b border-zinc-200 pb-3">
                            <CouponField :coupon-code="couponCode" />
                        </div>

                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3"
                        >
                            <dt>Pajak</dt>
                            <dd class="text-base text-zinc-900">
                                {{
                                    formatMoney(
                                        cartContext?.taxTotal ?? 0,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3"
                        >
                            <dt>Ongkir</dt>
                            <dd class="text-base text-zinc-900">
                                <template
                                    v-if="
                                        isMultiPackage &&
                                        allPackagesSelected
                                    "
                                    >{{
                                        multiShippingTotal > 0
                                            ? formatMoney(
                                                  multiShippingTotal,
                                                  multiShippingCurrency,
                                              )
                                            : 'Gratis'
                                    }}</template
                                >
                                <template v-else-if="selectedDelivery">{{
                                    selectedDelivery.amount > 0
                                        ? formatMoney(
                                              selectedDelivery.amount,
                                              selectedDelivery.currency,
                                          )
                                        : 'Gratis'
                                }}</template>
                                <template v-else
                                    >Dihitung di langkah berikutnya</template
                                >
                            </dd>
                        </div>

                        <div
                            v-if="cartContext && cartContext.discountTotal > 0"
                            class="flex items-center justify-between border-b border-zinc-200 pb-3"
                        >
                            <dt>Diskon</dt>
                            <dd class="text-emerald-600">
                                −{{
                                    formatMoney(
                                        cartContext.discountTotal,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <dt
                                class="text-base font-semibold text-zinc-900"
                            >
                                Total {{ taxLabel }}
                            </dt>
                            <dd
                                class="text-base font-semibold text-zinc-900"
                            >
                                {{ formatMoney(total, currency) }}
                            </dd>
                        </div>
                    </dl>
                </Card>
            </div>
        </div>
    </Container>
</template>
