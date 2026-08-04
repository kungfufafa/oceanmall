<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import { Button } from '@/components/ui/button';
import { useStripeElements } from '@/composables/useStripeElements';
import { cart as cartRoute } from '@/routes/shop';
import type { Order } from '@/types/shop';

const props = defineProps<{
    order: Order;
    clientSecret: string;
    publishableKey: string;
    returnUrl: string;
}>();

const paymentElementRef = ref<HTMLDivElement | null>(null);

const { ready, submitting, error, confirm } = useStripeElements(
    {
        publishableKey: props.publishableKey,
        clientSecret: props.clientSecret,
    },
    () => paymentElementRef.value,
);

async function pay(): Promise<void> {
    await confirm(props.returnUrl);
}
</script>

<template>
    <Head title="Selesaikan pembayaran" />

    <AppPageHeader
        class="lg:hidden"
        title="Pembayaran"
        :back-href="cartRoute.url()"
        max-width-class="max-w-7xl"
    />

    <Container class="py-8 lg:py-12">
        <div class="mx-auto max-w-xl">
            <h1 class="om-page-title hidden !text-lg lg:block">
                Selesaikan pembayaran
            </h1>
            <p class="om-meta mt-2">
                Pesanan
                <span class="font-mono text-zinc-800">{{ order.number }}</span>
            </p>

            <form class="mt-8 space-y-4" @submit.prevent="pay">
                <div ref="paymentElementRef" />

                <p v-if="error" class="text-[13px] text-red-600">{{ error }}</p>

                <Button
                    type="submit"
                    size="xl"
                    class="w-full"
                    :disabled="!ready || submitting"
                >
                    <span v-if="submitting">Memproses…</span>
                    <span v-else>Bayar sekarang</span>
                </Button>
            </form>
        </div>
    </Container>
</template>
