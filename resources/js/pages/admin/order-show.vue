<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatMoney } from '@/lib/format';
import { printLabel as printLabelRoute } from '@/routes/admin/orders';

type ShipmentLine = {
    id: number;
    name: string;
    purchasable_type: string;
    purchasable_id: number;
    qty: number;
};

type Shipment = {
    id: number;
    inventory_id: number | null;
    inventory_name: string | null;
    status: string;
    status_label: string;
    awb: string | null;
    tracking_number: string | null;
    carrier: string | null;
    service: string | null;
    cost: number;
    currency: string;
    delivery_order_no: string | null;
    can_print_label: boolean;
    print_hint: string | null;
    can_override: boolean;
    lines: ShipmentLine[];
};

type InventoryOption = {
    id: number;
    name: string;
    is_default: boolean;
    rajaongkir_origin_id: string | null;
    ready_for_shipping: boolean;
};

type Order = {
    id: number;
    number: string;
    status: string;
    payment_status: string;
    shipping_status: string;
    price_amount: number;
    currency_code: string;
    customer_email: string | null;
    customer_name: string | null;
};

const props = defineProps<{
    order: Order;
    shipments: Shipment[];
    inventories: InventoryOption[];
    komerceEnabled: boolean;
    canPrintAnyLabel: boolean;
    printableShipmentCount: number;
}>();

const page = usePage();
const printing = ref(false);

const flashLabelError = computed(() => {
    const errors = page.props.errors as Record<string, string> | undefined;

    return errors?.label ?? null;
});

const printError = ref<string | null>(null);
const overrideError = ref<string | null>(null);

const firstOverridableLine = computed(() => {
    const shipment = props.shipments.find((item) => item.can_override);

    return shipment?.lines[0] ?? null;
});

const overrideForm = useForm({
    moves: [
        {
            shipment_line_id: firstOverridableLine.value?.id ?? null,
            qty: 1,
            from_inventory_id: props.shipments.find((s) => s.can_override)?.inventory_id ?? null,
            to_inventory_id:
                props.inventories.find(
                    (inventory) =>
                        inventory.id !==
                        props.shipments.find((s) => s.can_override)?.inventory_id,
                )?.id ?? null,
        },
    ],
});

const overridableShipments = computed(() =>
    props.shipments.filter((shipment) => shipment.can_override),
);

const lineOptions = computed(() =>
    overridableShipments.value.flatMap((shipment) =>
        shipment.lines.map((line) => ({
            id: line.id,
            label: `${line.name} ×${line.qty} · ${shipment.inventory_name ?? 'Warehouse'}`,
            inventory_id: shipment.inventory_id,
        })),
    ),
);

function onLineChange(): void {
    const selected = lineOptions.value.find(
        (option) => option.id === Number(overrideForm.moves[0].shipment_line_id),
    );

    if (selected) {
        overrideForm.moves[0].from_inventory_id = selected.inventory_id;
    }
}

function printLabel(shipmentId?: number): void {
    printError.value = null;

    if (!props.komerceEnabled) {
        printError.value =
            'Komerce shipping is not configured. Add the Shipping Delivery API key in .env, then reload.';
        return;
    }

    if (shipmentId === undefined && !props.canPrintAnyLabel) {
        printError.value =
            'No labels are ready yet. Labels appear after payment clears and RajaOngkir creates the delivery order.';
        return;
    }

    const shipment =
        shipmentId !== undefined
            ? props.shipments.find((item) => item.id === shipmentId)
            : undefined;

    if (shipment && !shipment.can_print_label) {
        printError.value =
            shipment.print_hint ??
            'This shipment is not ready for a label yet.';
        return;
    }

    printing.value = true;

    const url = printLabelRoute.url(props.order.id, {
        query: shipmentId ? { shipment: shipmentId } : {},
    });

    window.location.assign(url);
}

function submitOverride(): void {
    overrideError.value = null;
    overrideForm.post(`/admin/orders/${props.order.id}/override-allocation`, {
        preserveScroll: true,
        onError: (errors) => {
            overrideError.value =
                errors.moves ??
                errors.shipment ??
                errors.stock ??
                errors.qty ??
                'Could not move stock. Check quantities and try again.';
        },
        onSuccess: () => {
            router.reload({ only: ['shipments', 'order', 'canPrintAnyLabel', 'printableShipmentCount'] });
        },
    });
}
</script>

<template>
    <Head :title="`Ops · Order ${order.number}`" />

    <div class="mx-auto max-w-5xl space-y-8 p-6">
        <header class="space-y-2">
            <p class="text-sm font-medium text-zinc-500">Order operations</p>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                #{{ order.number }}
            </h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                {{ order.customer_name ?? order.customer_email ?? 'Customer' }}
                <span v-if="order.customer_name && order.customer_email">
                    · {{ order.customer_email }}
                </span>
            </p>
            <div class="flex flex-wrap gap-2 pt-1 text-xs">
                <span
                    class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    Payment · {{ order.payment_status }}
                </span>
                <span
                    class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    Shipping · {{ order.shipping_status }}
                </span>
                <span
                    class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    Order · {{ order.status }}
                </span>
                <span
                    class="rounded-full bg-zinc-100 px-2.5 py-1 font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
                >
                    {{ formatMoney(order.price_amount, order.currency_code) }}
                </span>
            </div>

            <p
                v-if="!komerceEnabled"
                class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/40 dark:text-amber-100"
                role="status"
            >
                Komerce shipping is off. Label printing and AWB creation stay
                unavailable until API keys are configured.
            </p>
        </header>

        <section class="space-y-4" aria-labelledby="shipments-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2
                        id="shipments-heading"
                        class="text-lg font-medium text-zinc-900 dark:text-white"
                    >
                        Shipments
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        <template v-if="printableShipmentCount > 0">
                            {{ printableShipmentCount }} ready for RajaOngkir
                            labels.
                        </template>
                        <template v-else>
                            Labels unlock after payment and delivery-order
                            creation.
                        </template>
                    </p>
                </div>
                <Button
                    type="button"
                    :disabled="printing || !komerceEnabled || !canPrintAnyLabel"
                    :title="
                        !canPrintAnyLabel
                            ? 'No delivery orders ready to print yet'
                            : 'Print every ready label'
                    "
                    @click="printLabel()"
                >
                    {{ printing ? 'Opening…' : 'Print all labels' }}
                </Button>
            </div>

            <p
                v-if="flashLabelError || printError"
                class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100"
                role="alert"
            >
                {{ flashLabelError || printError }}
            </p>

            <div
                v-for="shipment in shipments"
                :key="shipment.id"
                class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-zinc-900 dark:text-white">
                                {{
                                    shipment.inventory_name ??
                                    `Warehouse #${shipment.inventory_id}`
                                }}
                            </p>
                            <span
                                class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-950/50 dark:text-sky-100"
                            >
                                {{ shipment.status_label }}
                            </span>
                        </div>
                        <p class="text-sm text-zinc-500">
                            {{ shipment.carrier ?? 'Courier' }}
                            <template v-if="shipment.service">
                                · {{ shipment.service }}
                            </template>
                            ·
                            {{ formatMoney(shipment.cost, shipment.currency) }}
                        </p>
                        <dl class="grid gap-1 text-sm text-zinc-600 dark:text-zinc-300 sm:grid-cols-2">
                            <div>
                                <dt class="inline text-zinc-400">AWB</dt>
                                ·
                                <dd class="inline font-medium">
                                    {{ shipment.awb ?? 'Not assigned' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="inline text-zinc-400">Delivery #</dt>
                                ·
                                <dd class="inline font-medium">
                                    {{
                                        shipment.delivery_order_no ??
                                        'Pending'
                                    }}
                                </dd>
                            </div>
                        </dl>
                        <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                            <li
                                v-for="line in shipment.lines"
                                :key="line.id"
                                class="truncate"
                            >
                                {{ line.name }}
                                <span class="text-zinc-400">×{{ line.qty }}</span>
                            </li>
                        </ul>
                        <p
                            v-if="!shipment.can_print_label && shipment.print_hint"
                            class="text-xs text-zinc-500"
                        >
                            {{ shipment.print_hint }}
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        :disabled="
                            printing ||
                            !komerceEnabled ||
                            !shipment.can_print_label
                        "
                        :title="
                            shipment.can_print_label
                                ? 'Open RajaOngkir shipping label'
                                : (shipment.print_hint ?? 'Label not ready')
                        "
                        @click="printLabel(shipment.id)"
                    >
                        Print label
                    </Button>
                </div>
            </div>
        </section>

        <section
            v-if="overridableShipments.length"
            class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-950"
            aria-labelledby="override-heading"
        >
            <div>
                <h2
                    id="override-heading"
                    class="text-lg font-medium text-zinc-900 dark:text-white"
                >
                    Move stock before shipping
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Reassign quantity to another warehouse while the shipment is
                    still waiting for an AWB.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-2 sm:col-span-2 lg:col-span-2">
                    <Label for="shipment_line_id">Item to move</Label>
                    <select
                        id="shipment_line_id"
                        v-model="overrideForm.moves[0].shipment_line_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        @change="onLineChange"
                    >
                        <option
                            v-for="option in lineOptions"
                            :key="option.id"
                            :value="option.id"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="qty">Quantity</Label>
                    <Input
                        id="qty"
                        v-model="overrideForm.moves[0].qty"
                        type="number"
                        min="1"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="from_inventory_id">From</Label>
                    <select
                        id="from_inventory_id"
                        v-model="overrideForm.moves[0].from_inventory_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    >
                        <option
                            v-for="inventory in inventories"
                            :key="inventory.id"
                            :value="inventory.id"
                        >
                            {{ inventory.name }}
                            <template v-if="!inventory.ready_for_shipping">
                                (no RajaOngkir origin)
                            </template>
                        </option>
                    </select>
                </div>
                <div class="space-y-2 sm:col-span-2 lg:col-span-2">
                    <Label for="to_inventory_id">To</Label>
                    <select
                        id="to_inventory_id"
                        v-model="overrideForm.moves[0].to_inventory_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                    >
                        <option
                            v-for="inventory in inventories"
                            :key="inventory.id"
                            :value="inventory.id"
                        >
                            {{ inventory.name }}
                            <template v-if="inventory.is_default">
                                · default
                            </template>
                            <template v-if="!inventory.ready_for_shipping">
                                · missing origin id
                            </template>
                        </option>
                    </select>
                </div>
            </div>

            <p
                v-if="overrideError"
                class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100"
                role="alert"
            >
                {{ overrideError }}
            </p>

            <Button
                type="button"
                :disabled="overrideForm.processing"
                @click="submitOverride"
            >
                {{ overrideForm.processing ? 'Saving…' : 'Apply move' }}
            </Button>
        </section>
    </div>
</template>
