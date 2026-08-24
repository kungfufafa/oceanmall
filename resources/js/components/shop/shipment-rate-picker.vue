<script setup lang="ts">
import { computed } from 'vue';
import SelectableCard from '@/components/shop/selectable-card.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { RadioGroup } from '@/components/ui/radio-group';
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

const selected = computed<Record<number | string, string>>(
    () => props.modelValue,
);

function selectRate(inventoryId: number, serviceCode: unknown): void {
    if (typeof serviceCode !== 'string' || serviceCode === '') {
return;
}

    emit('update:modelValue', {
        ...selected.value,
        [inventoryId]: serviceCode,
    });
}

function selectedRateFor(inventoryId: number): string {
    return String(selected.value[inventoryId] ?? '');
}

function optionsFor(inventoryId: number): DeliveryOption[] {
    return props.deliveryOptionsByShipment[inventoryId] ?? [];
}

const totalShipping = computed<number>(() => {
    let sum = 0;

    for (const pkg of props.packages) {
        const code = selected.value[pkg.inventory_id];

        if (!code) {
continue;
}

        const opt = optionsFor(pkg.inventory_id).find(
            (o) => String(o.service_code) === String(code),
        );

        if (opt) {
sum += opt.amount;
}
    }

    return sum;
});

const totalCurrency = computed<string>(() => {
    for (const pkg of props.packages) {
        const code = selected.value[pkg.inventory_id];

        if (!code) {
continue;
}

        const opt = optionsFor(pkg.inventory_id).find(
            (o) => String(o.service_code) === String(code),
        );

        if (opt?.currency) {
return opt.currency;
}
    }

    return 'IDR';
});

const allSelected = computed<boolean>(() =>
    props.packages.every((pkg) => Boolean(selected.value[pkg.inventory_id])),
);

function formatCourierTitle(option: DeliveryOption): string {
    const carrier =
        option.carrier_name ||
        (option.carrier_code ? option.carrier_code.toUpperCase() : '');

    if (!carrier) {
return option.service_name;
}

    if (option.service_name.toLowerCase().includes(carrier.toLowerCase())) {
        return option.service_name;
    }

    return `${carrier} - ${option.service_name}`;
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <Card
            v-for="(pkg, pkgIndex) in packages"
            :key="pkg.inventory_id"
            class="gap-0 rounded-md border-border bg-card py-0 text-card-foreground shadow-none"
        >
            <CardHeader class="flex flex-row items-center gap-2 p-4 pb-0">
                <Badge variant="secondary">Paket {{ pkgIndex + 1 }}</Badge>
                <CardTitle class="text-sm">
                    {{ pkg.inventory_name }}
                </CardTitle>
            </CardHeader>

            <CardContent class="p-4">
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

                <RadioGroup
                    v-else
                    :model-value="selectedRateFor(pkg.inventory_id)"
                    class="flex flex-col gap-2"
                    @update:model-value="
                        (value) => selectRate(pkg.inventory_id, value)
                    "
                >
                    <SelectableCard
                        v-for="option in optionsFor(pkg.inventory_id)"
                        :key="String(option.service_code)"
                        :id="`shipment_rate_${pkg.inventory_id}_${option.service_code}`"
                        :value="String(option.service_code)"
                        class="items-center p-3"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="option.carrier_logo"
                                    :src="option.carrier_logo"
                                    :alt="option.carrier_name ?? ''"
                                    class="mt-0.5 size-5 rounded-full object-cover"
                                />
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ formatCourierTitle(option) }}
                                    </span>
                                    <span
                                        v-if="option.estimated_days"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ option.estimated_days }} hari
                                    </span>
                                    <span
                                        v-else-if="option.description"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ option.description }}
                                    </span>
                                </div>
                            </div>
                            <span
                                class="shrink-0 text-sm font-medium text-foreground"
                            >
                                {{
                                    formatMoney(option.amount, option.currency)
                                }}
                            </span>
                        </div>
                    </SelectableCard>
                </RadioGroup>
            </CardContent>
        </Card>

        <Card
            v-if="packages.length > 1 && allSelected"
            class="gap-0 rounded-md border-border bg-muted py-0 text-card-foreground shadow-none"
        >
            <CardFooter class="justify-between px-4 py-3 text-sm">
                <span class="text-muted-foreground">Total ongkir</span>
                <span class="font-semibold text-foreground">{{
                    formatMoney(totalShipping, totalCurrency)
                }}</span>
            </CardFooter>
        </Card>
    </div>
</template>
