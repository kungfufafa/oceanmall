<script setup lang="ts">
import { computed } from 'vue';
import { formatMoney } from '@/lib/format';
import type { DeliveryOption } from '@/types/shop';

type ShipmentPackage = {
    inventory_id: number;
    inventory_name: string;
    lines: Array<{
        purchasable_type: string;
        purchasable_id: number;
        qty: number;
    }>;
};

const props = defineProps<{
    packages: ShipmentPackage[];
    deliveryOptionsByShipment: Record<number | string, DeliveryOption[]>;
    modelValue: Record<number | string, string>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: Record<number | string, string>];
}>();

const selected = computed<Record<number | string, string>>(() => props.modelValue);

function selectRate(inventoryId: number, serviceCode: string): void {
    emit('update:modelValue', { ...selected.value, [inventoryId]: serviceCode });
}

function optionsFor(inventoryId: number): DeliveryOption[] {
    return props.deliveryOptionsByShipment[inventoryId] ?? [];
}

const totalShipping = computed<number>(() => {
    let sum = 0;

    for (const pkg of props.packages) {
        const code = selected.value[pkg.inventory_id];
        if (!code) continue;
        const opt = optionsFor(pkg.inventory_id).find(
            (o) => String(o.service_code) === String(code),
        );
        if (opt) sum += opt.amount;
    }

    return sum;
});

const totalCurrency = computed<string>(() => {
    for (const pkg of props.packages) {
        const code = selected.value[pkg.inventory_id];
        if (!code) continue;
        const opt = optionsFor(pkg.inventory_id).find(
            (o) => String(o.service_code) === String(code),
        );
        if (opt?.currency) return opt.currency;
    }
    return 'IDR';
});

const allSelected = computed<boolean>(() =>
    props.packages.every((pkg) => Boolean(selected.value[pkg.inventory_id])),
);

defineExpose({ totalShipping, allSelected });
</script>

<template>
    <div class="space-y-6">
        <div
            v-for="(pkg, pkgIndex) in packages"
            :key="pkg.inventory_id"
            class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
        >
            <h3
                class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white"
            >
                Paket {{ pkgIndex + 1 }} &middot; {{ pkg.inventory_name }}
            </h3>

            <div
                v-if="!optionsFor(pkg.inventory_id).length"
                class="text-sm text-zinc-500"
            >
                No delivery options available for this package.
            </div>

            <div v-else class="flex flex-col gap-2">
                <label
                    v-for="option in optionsFor(pkg.inventory_id)"
                    :key="String(option.service_code)"
                    :class="[
                        'flex cursor-pointer items-center justify-between gap-4 rounded-lg p-3 transition',
                        String(selected[pkg.inventory_id]) ===
                        String(option.service_code)
                            ? 'ring-2 ring-zinc-900 dark:ring-white'
                            : 'ring-1 ring-zinc-200 hover:ring-zinc-300 dark:ring-zinc-700',
                    ]"
                >
                    <input
                        type="radio"
                        :name="`shipment_rate_${pkg.inventory_id}`"
                        :value="String(option.service_code)"
                        :checked="
                            String(selected[pkg.inventory_id]) ===
                            String(option.service_code)
                        "
                        class="sr-only"
                        @change="selectRate(pkg.inventory_id, String(option.service_code))"
                    />
                    <div class="flex items-start gap-3">
                        <img
                            v-if="option.carrier_logo"
                            :src="option.carrier_logo"
                            :alt="option.carrier_name ?? ''"
                            class="mt-0.5 size-5 rounded-full object-cover"
                        />
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-medium text-zinc-900 dark:text-white"
                                >{{ option.service_name }}</span
                            >
                            <span
                                v-if="option.estimated_days"
                                class="text-xs text-zinc-500"
                                >{{ option.estimated_days }} days</span
                            >
                            <span
                                v-else-if="option.description"
                                class="text-xs text-zinc-500"
                                >{{ option.description }}</span
                            >
                        </div>
                    </div>
                    <span
                        class="shrink-0 text-sm font-medium text-zinc-900 dark:text-white"
                        >{{ formatMoney(option.amount, option.currency) }}</span
                    >
                </label>
            </div>
        </div>

        <div
            v-if="packages.length > 1 && allSelected"
            class="flex items-center justify-between rounded-lg bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-800"
        >
            <span class="text-zinc-600 dark:text-zinc-400">Total shipping</span>
            <span class="font-semibold text-zinc-900 dark:text-white">{{
                formatMoney(totalShipping, totalCurrency)
            }}</span>
        </div>
    </div>
</template>
