<script setup lang="ts">
import { computed } from 'vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
    emptyHint?: string | null;
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
    <div class="flex flex-col gap-6">
        <div
            v-for="(pkg, pkgIndex) in packages"
            :key="pkg.inventory_id"
            class="rounded-md border border-zinc-200 p-4"
        >
            <h3 class="mb-3 flex items-center gap-2">
                <Badge variant="secondary">Paket {{ pkgIndex + 1 }}</Badge>
                <span class="om-page-title !text-sm">
                    {{ pkg.inventory_name }}
                </span>
            </h3>

            <Alert
                v-if="!optionsFor(pkg.inventory_id).length"
                variant="warning"
            >
                <AlertDescription class="text-sm text-current">
                    {{
                        emptyHint ||
                        'Belum ada opsi pengiriman untuk paket ini. Pastikan gudang punya origin RajaOngkir dan destinasi sudah dipilih.'
                    }}
                </AlertDescription>
            </Alert>

            <div v-else class="flex flex-col gap-2">
                <label
                    v-for="option in optionsFor(pkg.inventory_id)"
                    :key="String(option.service_code)"
                    :class="[
                        'flex cursor-pointer items-center justify-between gap-4 rounded-md p-3 transition',
                        String(selected[pkg.inventory_id]) ===
                        String(option.service_code)
                            ? 'ring-2 ring-[var(--om-navy)]'
                            : 'ring-1 ring-zinc-200 hover:ring-zinc-300',
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
                                class="text-sm font-medium text-[var(--om-navy)]"
                                >{{ option.service_name }}</span
                            >
                            <span
                                v-if="option.estimated_days"
                                class="om-meta !text-xs"
                                >{{ option.estimated_days }} hari</span
                            >
                            <span
                                v-else-if="option.description"
                                class="om-meta !text-xs"
                                >{{ option.description }}</span
                            >
                        </div>
                    </div>
                    <span
                        class="shrink-0 text-sm font-medium text-[var(--om-navy)]"
                        >{{ formatMoney(option.amount, option.currency) }}</span
                    >
                </label>
            </div>
        </div>

        <div
            v-if="packages.length > 1 && allSelected"
            class="flex items-center justify-between rounded-md border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm"
        >
            <span class="text-zinc-600">Total ongkir</span>
            <span class="font-semibold text-[var(--om-navy)]">{{
                formatMoney(totalShipping, totalCurrency)
            }}</span>
        </div>
    </div>
</template>
