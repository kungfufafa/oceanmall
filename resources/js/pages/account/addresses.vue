<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CreditCard,
    MoreHorizontal,
    Trash2,
    Truck,
} from 'lucide-vue-next';
import { ref } from 'vue';
import Card from '@/components/shop/card.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AddressController from '@/actions/App/Http/Controllers/Account/AddressController';
import { AddressType, type Address } from '@/types/shop';

type CountryOption = { id: number; name: string; cca2: string };

type AddressForm = {
    first_name: string;
    last_name: string;
    street_address: string;
    street_address_plus: string;
    postal_code: string;
    city: string;
    state: string;
    phone_number: string;
    country_id: number | null;
    type: AddressType;
};

const props = defineProps<{
    addresses: Address[];
    countries: CountryOption[];
}>();

const editing = ref<Address | null>(null);
const open = ref<boolean>(false);

const defaults: AddressForm = {
    first_name: '',
    last_name: '',
    street_address: '',
    street_address_plus: '',
    postal_code: '',
    city: '',
    state: '',
    phone_number: '',
    country_id: null,
    type: AddressType.SHIPPING,
};

const form = useForm<AddressForm>({ ...defaults });

function startCreate(): void {
    editing.value = null;
    form.reset();
    Object.assign(form, defaults);
    open.value = true;
}

function startEdit(address: Address): void {
    editing.value = address;
    form.first_name = address.first_name ?? '';
    form.last_name = address.last_name;
    form.street_address = address.street_address;
    form.street_address_plus = address.street_address_plus ?? '';
    form.postal_code = address.postal_code;
    form.city = address.city;
    form.state = address.state ?? '';
    form.phone_number = address.phone_number ?? '';
    form.country_id = address.country_id;
    form.type = address.type;
    open.value = true;
}

function submit(): void {
    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            editing.value = null;
            form.reset();
        },
    };

    if (editing.value) {
        form.patch(AddressController.update.url(editing.value.id), opts);
    } else {
        form.post(AddressController.store.url(), opts);
    }
}

function destroy(address: Address): void {
    if (!window.confirm('Do you really want to delete this address?')) return;
    router.delete(AddressController.destroy.url(address.id), {
        preserveScroll: true,
    });
}

function setDefaultShipping(address: Address): void {
    router.patch(
        AddressController.setDefaultShipping.url(address.id),
        {},
        { preserveScroll: true },
    );
}

function setDefaultBilling(address: Address): void {
    router.patch(
        AddressController.setDefaultBilling.url(address.id),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="My Addresses" />

    <div>
        <h1
            class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
        >
            My Addresses
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            View and update your delivery and billing addresses.
        </p>
    </div>

    <div class="mt-8 space-y-8">
        <Button
            type="button"
            variant="outline"
            class="w-full sm:w-auto"
            @click="startCreate"
            >Add address</Button
        >

        <div
            v-if="addresses.length"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <Card
                v-for="address in addresses"
                :key="address.id"
                class="flex flex-col justify-between"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-2">
                        <h4
                            class="font-heading text-sm font-medium text-zinc-900 dark:text-white"
                        >
                            {{ address.first_name }} {{ address.last_name }}
                        </h4>
                        <span
                            v-if="address.type === AddressType.BILLING"
                            class="inline-flex shrink-0 items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                            >Billing</span
                        >
                    </div>

                    <address
                        class="flex flex-col text-sm text-zinc-500 not-italic"
                    >
                        <span>
                            {{ address.street_address
                            }}<span v-if="address.street_address_plus"
                                >, {{ address.street_address_plus }}</span
                            >
                        </span>
                        <span
                            >{{ address.postal_code }}, {{ address.city }}</span
                        >
                        <span v-if="address.country">{{
                            address.country.name
                        }}</span>
                    </address>

                    <div class="space-y-1">
                        <span
                            v-if="address.shipping_default"
                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                        >
                            <Check class="size-3" aria-hidden="true" />
                            Default shipping
                        </span>
                        <span
                            v-if="address.billing_default"
                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200"
                        >
                            <Check class="size-3" aria-hidden="true" />
                            Default billing
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        @click="destroy(address)"
                    >
                        <Trash2 class="size-3.5" aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="startEdit(address)"
                    >
                        Edit
                    </Button>
                    <DropdownMenu
                        v-if="
                            !address.shipping_default || !address.billing_default
                        "
                    >
                        <DropdownMenuTrigger as-child>
                            <Button type="button" size="sm" variant="outline">
                                <MoreHorizontal
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-if="!address.shipping_default"
                                @click="setDefaultShipping(address)"
                            >
                                <Truck class="size-4" aria-hidden="true" />
                                Set as default shipping
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="!address.billing_default"
                                @click="setDefaultBilling(address)"
                            >
                                <CreditCard
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                Set as default billing
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </Card>
        </div>

        <p v-else class="text-sm text-zinc-500">
            You have not yet added any addresses.
        </p>
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-2xl">
            <DialogTitle
                class="text-lg font-semibold text-zinc-900 dark:text-white"
            >
                {{ editing ? 'Update address' : 'Add new address' }}
            </DialogTitle>
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="first_name">First name</Label>
                        <Input
                            id="first_name"
                            v-model="form.first_name"
                            required
                        />
                        <p
                            v-if="form.errors.first_name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.first_name }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="last_name">Last name</Label>
                        <Input
                            id="last_name"
                            v-model="form.last_name"
                            required
                        />
                        <p
                            v-if="form.errors.last_name"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.last_name }}
                        </p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="street_address">Street address</Label>
                        <Input
                            id="street_address"
                            v-model="form.street_address"
                            required
                        />
                        <p
                            v-if="form.errors.street_address"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.street_address }}
                        </p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="street_address_plus"
                            >Apartment, suite, etc.</Label
                        >
                        <Input
                            id="street_address_plus"
                            v-model="form.street_address_plus"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="city">City</Label>
                        <Input id="city" v-model="form.city" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="postal_code">Postal / Zip code</Label>
                        <Input
                            id="postal_code"
                            v-model="form.postal_code"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="state">State / Province</Label>
                        <Input id="state" v-model="form.state" />
                    </div>
                    <div class="space-y-2">
                        <Label for="country_id">Country</Label>
                        <Select
                            :model-value="form.country_id?.toString() ?? ''"
                            @update:model-value="
                                (v) => (form.country_id = v ? Number(v) : null)
                            "
                        >
                            <SelectTrigger
                                id="country_id"
                                aria-label="Country"
                                class="w-full"
                            >
                                <SelectValue placeholder="Select a country" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="country in countries"
                                    :key="country.id"
                                    :value="country.id.toString()"
                                >
                                    {{ country.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.country_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ form.errors.country_id }}
                        </p>
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="phone_number">Phone number</Label>
                        <Input
                            id="phone_number"
                            v-model="form.phone_number"
                        />
                    </div>
                    <fieldset class="space-y-2 sm:col-span-2">
                        <legend
                            class="text-sm font-medium text-zinc-900 dark:text-white"
                        >
                            Address type
                        </legend>
                        <div class="flex flex-wrap items-center gap-6 pt-1">
                            <label
                                class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"
                            >
                                <input
                                    v-model="form.type"
                                    type="radio"
                                    :value="AddressType.SHIPPING"
                                    class="border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600"
                                />
                                Shipping address
                            </label>
                            <label
                                class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"
                            >
                                <input
                                    v-model="form.type"
                                    type="radio"
                                    :value="AddressType.BILLING"
                                    class="border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600"
                                />
                                Billing address
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div class="flex justify-end gap-3">
                    <Button type="button" variant="ghost" @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">{{
                        editing ? 'Save' : 'Save'
                    }}</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
