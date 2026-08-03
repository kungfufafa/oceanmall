<script setup lang="ts">
import { Lock } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useStripeElements } from '@/composables/useStripeElements';
import { useShop } from '@/composables/useShop';
import { formatMoney } from '@/lib/format';

const props = defineProps<{
    clientSecret: string;
    publishableKey: string;
    returnUrl: string;
    total?: number;
}>();

const mount = ref<HTMLElement | null>(null);
const { currency, taxLabel } = useShop();

const { submitting, error, confirm } = useStripeElements(
    {
        clientSecret: props.clientSecret,
        publishableKey: props.publishableKey,
    },
    () => mount.value,
);

async function pay(): Promise<void> {
    await confirm(props.returnUrl);
}
</script>

<template>
    <div class="space-y-4">
        <div ref="mount" class="min-h-[240px]" />

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <div
            class="flex flex-col gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-700"
        >
            <div class="flex items-center justify-between gap-4">
                <div v-if="total !== undefined" class="flex flex-col">
                    <span class="text-xs text-zinc-500"
                        >Total {{ taxLabel }}</span
                    >
                    <span
                        class="text-lg font-semibold text-zinc-900 dark:text-white"
                        >{{ formatMoney(total, currency) }}</span
                    >
                </div>
                <Button type="button" :disabled="submitting" @click="pay">
                    {{ submitting ? 'Processing...' : 'Pay now' }}
                </Button>
            </div>
            <p class="inline-flex items-center gap-1.5 text-xs text-zinc-500">
                <Lock class="size-3" aria-hidden="true" />
                Secure &amp; encrypted
            </p>
        </div>
    </div>
</template>
