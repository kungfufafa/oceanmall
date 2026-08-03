<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, ChevronRight, Lock, ShoppingBag } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Card from '@/components/shop/card.vue';
import Container from '@/components/shop/container.vue';
import StripePaymentForm from '@/components/shop/stripe-payment-form.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';
import * as checkout from '@/routes/shop/checkout';
import type {
    Address,
    Cart,
    CartContext,
    DeliveryOption,
    PaymentMethod,
} from '@/types/shop';

type ShippingAddressForm = {
    first_name: string;
    last_name: string;
    street_address: string;
    street_address_plus: string;
    postal_code: string;
    city: string;
    state: string;
    phone_number: string;
};

const props = defineProps<{
    cart: Cart;
    cartContext: CartContext;
    savedAddresses: Address[];
    shippingAddress: ShippingAddressForm | null;
    deliveryOptions: DeliveryOption[];
    selectedDeliveryOption: string | number | null;
    paymentOptions: Array<{
        id: number;
        title: string;
        driver: string;
        logo?: string | null;
    }>;
    selectedPaymentMethod: number | null;
    step: 1 | 2 | 3;
    stripeData: {
        client_secret: string;
        publishable_key: string;
        return_url: string;
    } | null;
}>();

const { currency, taxLabel, zone } = useShop();

const step = computed<1 | 2 | 3>(() => props.step);

const maxStep = computed<1 | 2 | 3>(() => {
    if (props.selectedDeliveryOption !== null) return 3;
    if (props.shippingAddress) return 2;
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
});

const shippingForm = useForm<{ service_code: string }>({
    service_code: props.selectedDeliveryOption ?? '',
});

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
const preparingStripe = ref<boolean>(false);
const stripeMounted = ref<boolean>(false);

watch(
    () => isStripeSelected.value && Boolean(props.stripeData),
    (active) => {
        if (active) stripeMounted.value = true;
    },
    { immediate: true },
);

watch(
    () => paymentForm.payment_method_id,
    (id) => {
        if (!id) return;
        const method = props.paymentOptions.find((m) => m.id === id) ?? null;
        if (!method) return;

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
    const delivery = selectedDelivery.value?.amount ?? 0;
    return sub + delivery;
});

function selectAddress(address: Address): void {
    selectedAddressId.value = address.id;
    addressForm.first_name = address.first_name;
    addressForm.last_name = address.last_name;
    addressForm.street_address = address.street_address;
    addressForm.street_address_plus = address.street_address_plus ?? '';
    addressForm.postal_code = address.postal_code;
    addressForm.city = address.city;
    addressForm.state = address.state ?? '';
    addressForm.phone_number = address.phone_number ?? '';
}

function clearAddress(): void {
    selectedAddressId.value = null;
    addressForm.reset();
}

function goToStep(target: 1 | 2 | 3): void {
    if (target === step.value) return;
    if (target > maxStep.value) return;
    router.get(
        checkout.index.url(),
        { step: target },
        { preserveScroll: true, preserveState: false },
    );
}

function submitAddress(): void {
    addressForm.post(checkout.shippingAddress.url(), { preserveScroll: true });
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
    { n: 1, label: 'Shipping' },
    { n: 2, label: 'Delivery' },
    { n: 3, label: 'Payment' },
] as const;
</script>

<template>
    <Head title="Checkout" />

    <Container class="py-8 sm:py-12">
        <h1
            class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
        >
            Checkout
        </h1>

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
                                ? 'text-zinc-900 dark:text-white'
                                : maxStep > s.n
                                  ? 'text-green-600'
                                  : 'text-zinc-400 dark:text-zinc-500',
                        ]"
                        @click="goToStep(s.n as 1 | 2 | 3)"
                    >
                        <span
                            :class="[
                                'flex size-7 items-center justify-center rounded-full text-xs font-bold',
                                step === s.n
                                    ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900'
                                    : step > s.n
                                      ? 'bg-green-100 text-green-600'
                                      : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500',
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
                        class="size-4 text-zinc-300 dark:text-zinc-600"
                        aria-hidden="true"
                    />
                </li>
            </ol>
        </nav>

        <div class="lg:grid lg:grid-cols-12 lg:gap-x-12">
            <div class="lg:col-span-7">
                <template v-if="step === 1">
                    <div v-if="savedAddresses.length" class="mb-8">
                        <h2
                            class="text-lg font-semibold text-zinc-900 dark:text-white"
                        >
                            Saved Addresses
                        </h2>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="address in savedAddresses"
                                :key="address.id"
                                type="button"
                                :class="[
                                    'rounded-xl text-left transition',
                                    selectedAddressId === address.id
                                        ? 'ring-2 ring-zinc-900 dark:ring-white'
                                        : 'ring-1 ring-zinc-200 hover:ring-zinc-400 dark:ring-zinc-700 dark:hover:ring-zinc-500',
                                ]"
                                @click="selectAddress(address)"
                            >
                                <Card>
                                    <p
                                        class="text-sm font-medium text-zinc-900 dark:text-white"
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
                                        class="mt-2 inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    >
                                        Default
                                    </span>
                                </Card>
                            </button>
                        </div>

                        <button
                            v-if="selectedAddressId"
                            type="button"
                            class="mt-3 text-sm text-zinc-500 underline transition hover:text-zinc-900 dark:hover:text-white"
                            @click="clearAddress"
                        >
                            Use a new address instead
                        </button>

                        <hr
                            class="my-6 border-zinc-200/60 dark:border-zinc-700/60"
                        />
                    </div>

                    <form class="space-y-5" @submit.prevent="submitAddress">
                        <h2
                            class="text-lg font-semibold text-zinc-900 dark:text-white"
                        >
                            Shipping Address
                        </h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="first_name">First name</Label>
                                <Input
                                    id="first_name"
                                    v-model="addressForm.first_name"
                                />
                                <p
                                    v-if="addressForm.errors.first_name"
                                    class="text-xs text-red-600"
                                >
                                    {{ addressForm.errors.first_name }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="last_name">Last name</Label>
                                <Input
                                    id="last_name"
                                    v-model="addressForm.last_name"
                                />
                                <p
                                    v-if="addressForm.errors.last_name"
                                    class="text-xs text-red-600"
                                >
                                    {{ addressForm.errors.last_name }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="street_address">Address</Label>
                            <Input
                                id="street_address"
                                v-model="addressForm.street_address"
                            />
                            <p
                                v-if="addressForm.errors.street_address"
                                class="text-xs text-red-600"
                            >
                                {{ addressForm.errors.street_address }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="street_address_plus"
                                >Apartment, suite, etc. (optional)</Label
                            >
                            <Input
                                id="street_address_plus"
                                v-model="addressForm.street_address_plus"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="city">City</Label>
                                <Input id="city" v-model="addressForm.city" />
                                <p
                                    v-if="addressForm.errors.city"
                                    class="text-xs text-red-600"
                                >
                                    {{ addressForm.errors.city }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="postal_code">Postal code</Label>
                                <Input
                                    id="postal_code"
                                    v-model="addressForm.postal_code"
                                />
                                <p
                                    v-if="addressForm.errors.postal_code"
                                    class="text-xs text-red-600"
                                >
                                    {{ addressForm.errors.postal_code }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="state">State / Province</Label>
                                <Input id="state" v-model="addressForm.state" />
                                <p
                                    v-if="addressForm.errors.state"
                                    class="text-xs text-red-600"
                                >
                                    {{ addressForm.errors.state }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <Label for="country">Country</Label>
                                <Input
                                    id="country"
                                    :value="zone?.country_name ?? ''"
                                    readonly
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="phone_number">Phone (optional)</Label>
                            <Input
                                id="phone_number"
                                v-model="addressForm.phone_number"
                                type="tel"
                            />
                        </div>

                        <div class="flex">
                            <Button
                                type="submit"
                                :disabled="addressForm.processing"
                            >
                                Continue to Delivery
                            </Button>
                        </div>
                    </form>
                </template>

                <template v-else-if="step === 2">
                    <div v-if="!deliveryOptions.length">
                        <div
                            class="flex items-center gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                        >
                            <ShoppingBag
                                class="size-5 text-zinc-400"
                                aria-hidden="true"
                            />
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                No delivery option available for your address.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="mt-4 text-sm text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                            @click="goToStep(1)"
                        >
                            ← Return to shipping
                        </button>
                    </div>

                    <form
                        v-else
                        class="space-y-5"
                        @submit.prevent="submitShipping"
                    >
                        <h2
                            class="text-lg font-semibold text-zinc-900 dark:text-white"
                        >
                            Delivery Method
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
                                    'flex cursor-pointer items-center justify-between gap-4 rounded-xl p-4 transition',
                                    shippingForm.service_code ===
                                    option.service_code
                                        ? 'ring-2 ring-zinc-900 dark:ring-white'
                                        : 'ring-1 ring-zinc-200 hover:ring-zinc-300 dark:ring-zinc-700',
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
                                            class="font-heading text-sm font-medium text-zinc-900 dark:text-white"
                                            >{{ option.service_name }}</span
                                        >
                                        <span
                                            v-if="option.estimated_days"
                                            class="text-sm text-zinc-500"
                                            >{{ option.estimated_days }} days
                                            delivery</span
                                        >
                                        <span
                                            v-else-if="option.description"
                                            class="text-sm text-zinc-500"
                                            >{{ option.description }}</span
                                        >
                                    </div>
                                </div>
                                <span
                                    class="text-sm font-medium text-zinc-900 dark:text-white"
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
                            <Button
                                type="submit"
                                :disabled="
                                    !shippingForm.service_code ||
                                    shippingForm.processing
                                "
                            >
                                Continue to Payment
                            </Button>
                        </div>
                    </form>
                </template>

                <template v-else>
                    <div class="space-y-5">
                        <div>
                            <h2
                                class="text-lg font-semibold text-zinc-900 dark:text-white"
                            >
                                Payment Method
                            </h2>
                            <p class="text-sm text-zinc-500">
                                All transactions are secure and encrypted.
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
                            class="text-sm text-zinc-600 dark:text-zinc-400"
                        >
                            No payment methods available for your region.
                        </p>

                        <template v-else>
                            <div class="flex flex-col gap-1">
                                <label
                                    v-for="method in paymentOptions"
                                    :key="method.id"
                                    :class="[
                                        'group flex cursor-pointer items-center justify-between gap-6 rounded-lg px-3 py-3 transition',
                                        paymentForm.payment_method_id ===
                                        method.id
                                            ? 'bg-zinc-100 dark:bg-zinc-800'
                                            : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/40',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="[
                                                'inline-flex size-4 items-center justify-center rounded-full border-2 transition',
                                                paymentForm.payment_method_id ===
                                                method.id
                                                    ? 'border-zinc-900 dark:border-white'
                                                    : 'border-zinc-300 dark:border-zinc-600',
                                            ]"
                                        >
                                            <span
                                                v-if="
                                                    paymentForm.payment_method_id ===
                                                    method.id
                                                "
                                                class="size-2 rounded-full bg-zinc-900 dark:bg-white"
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
                                            class="text-sm font-medium text-zinc-900 dark:text-white"
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
                                v-show="isStripeSelected && stripeData"
                                class="space-y-4 pt-2"
                            >
                                <div class="flex items-center gap-3">
                                    <h3
                                        class="text-xs font-medium tracking-wider text-zinc-500 uppercase"
                                    >
                                        Card details
                                    </h3>
                                    <span
                                        class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"
                                    />
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

                            <template
                                v-if="currentPaymentMethod && !isStripeSelected"
                            >
                                <div
                                    class="flex flex-col gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-700"
                                >
                                    <div
                                        class="flex items-center justify-between gap-4"
                                    >
                                        <div class="flex flex-col">
                                            <span class="text-xs text-zinc-500"
                                                >Total {{ taxLabel }}</span
                                            >
                                            <span
                                                class="text-lg font-semibold text-zinc-900 dark:text-white"
                                                >{{
                                                    formatMoney(total, currency)
                                                }}</span
                                            >
                                        </div>
                                        <Button
                                            type="button"
                                            :disabled="paymentForm.processing"
                                            @click="placeOrder"
                                        >
                                            {{
                                                paymentForm.processing
                                                    ? 'Processing...'
                                                    : 'Place my order'
                                            }}
                                        </Button>
                                    </div>
                                    <p
                                        class="inline-flex items-center gap-1.5 text-xs text-zinc-500"
                                    >
                                        <Lock
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        Secure &amp; encrypted
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
                                    class="inline-block size-4 animate-spin rounded-full border-2 border-zinc-300 border-t-zinc-900 dark:border-zinc-700 dark:border-t-white"
                                />
                                Preparing secure payment form...
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div class="mt-8 lg:col-span-5 lg:mt-0">
                <Card class="p-6">
                    <h2
                        class="font-heading text-lg font-semibold text-zinc-900 dark:text-white"
                    >
                        Order Summary
                    </h2>

                    <ul
                        v-if="cart"
                        role="list"
                        class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700"
                    >
                        <li
                            v-for="line in cart.lines"
                            :key="line.id"
                            class="flex gap-3 py-3"
                        >
                            <div
                                class="size-14 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800"
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
                                        class="text-sm font-medium text-zinc-900 dark:text-white"
                                    >
                                        {{ lineName(line) }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        Qty: {{ line.quantity }}
                                    </p>
                                </div>
                                <p
                                    class="text-sm font-medium text-zinc-900 dark:text-white"
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
                        class="mt-4 space-y-3 border-t border-zinc-200 pt-4 text-sm text-zinc-500 dark:border-zinc-700"
                    >
                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Tax</dt>
                            <dd class="text-base text-zinc-900 dark:text-white">
                                {{
                                    formatMoney(
                                        cartContext?.taxTotal ?? 0,
                                        currency,
                                    )
                                }}
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Delivery</dt>
                            <dd class="text-base text-zinc-900 dark:text-white">
                                <template v-if="selectedDelivery">{{
                                    selectedDelivery.amount > 0
                                        ? formatMoney(
                                              selectedDelivery.amount,
                                              selectedDelivery.currency,
                                          )
                                        : 'Free'
                                }}</template>
                                <template v-else
                                    >Calculated at next step</template
                                >
                            </dd>
                        </div>

                        <div
                            v-if="cartContext && cartContext.discountTotal > 0"
                            class="flex items-center justify-between border-b border-zinc-200 pb-3 dark:border-zinc-700"
                        >
                            <dt>Discount</dt>
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
                                class="text-base font-semibold text-zinc-900 dark:text-white"
                            >
                                Total {{ taxLabel }}
                            </dt>
                            <dd
                                class="text-base font-semibold text-zinc-900 dark:text-white"
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
