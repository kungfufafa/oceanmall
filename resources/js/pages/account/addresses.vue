<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CreditCard,
    MoreHorizontal,
    Truck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthSelectField from '@/components/auth/auth-select-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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

const countryOptions = computed(() =>
    props.countries.map((country) => ({
        value: String(country.id),
        label: country.name,
    })),
);

const countryId = computed({
    get: () => form.country_id?.toString() ?? '',
    set: (value: string) => {
        form.country_id = value ? Number(value) : null;
    },
});

const canSaveAddress = computed(
    () =>
        form.first_name.trim().length > 0 &&
        form.last_name.trim().length > 0 &&
        form.street_address.trim().length > 0 &&
        form.city.trim().length > 0 &&
        form.postal_code.trim().length > 0 &&
        form.country_id !== null &&
        !form.processing,
);

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
    if (!window.confirm('Yakin ingin menghapus alamat ini?')) return;
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
    <Head title="Alamat" />

    <div class="flex items-center justify-between gap-3">
        <p class="om-meta">Alamat pengiriman & penagihan</p>
        <button type="button" class="om-action-primary" @click="startCreate">
            Tambah
        </button>
    </div>

    <div class="mt-4 space-y-3">
        <div
            v-if="addresses.length"
            class="space-y-3"
        >
            <div
                v-for="address in addresses"
                :key="address.id"
                class="rounded-md border border-zinc-200 bg-white p-3.5"
            >
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[13px] font-semibold text-zinc-900">
                        {{ address.first_name }} {{ address.last_name }}
                    </h4>
                    <span
                        v-if="address.type === AddressType.BILLING"
                        class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600"
                        >Penagihan</span
                    >
                </div>

                <address class="om-meta mt-2 not-italic leading-5">
                    <span class="block">
                        {{ address.street_address
                        }}<span v-if="address.street_address_plus"
                            >, {{ address.street_address_plus }}</span
                        >
                    </span>
                    <span class="block"
                        >{{ address.postal_code }}, {{ address.city }}</span
                    >
                    <span v-if="address.country" class="block">{{
                        address.country.name
                    }}</span>
                </address>

                <div class="mt-2 flex flex-wrap gap-1.5">
                    <span
                        v-if="address.shipping_default"
                        class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600"
                    >
                        <Check class="size-3" aria-hidden="true" />
                        Default kirim
                    </span>
                    <span
                        v-if="address.billing_default"
                        class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600"
                    >
                        <Check class="size-3" aria-hidden="true" />
                        Default tagih
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <button
                        type="button"
                        class="om-btn-outline inline-flex items-center justify-center px-3 text-[12px]"
                        style="height: 2.25rem"
                        @click="startEdit(address)"
                    >
                        Ubah
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-md px-3 text-[12px] font-semibold text-red-600"
                        @click="destroy(address)"
                    >
                        Hapus
                    </button>
                    <DropdownMenu
                        v-if="
                            !address.shipping_default || !address.billing_default
                        "
                    >
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="inline-flex size-9 items-center justify-center rounded-md border border-zinc-200 text-zinc-500"
                                aria-label="Lainnya"
                            >
                                <MoreHorizontal
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-if="!address.shipping_default"
                                @click="setDefaultShipping(address)"
                            >
                                <Truck class="size-4" aria-hidden="true" />
                                Jadikan default kirim
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="!address.billing_default"
                                @click="setDefaultBilling(address)"
                            >
                                <CreditCard
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                Jadikan default tagih
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <p v-else class="om-meta py-8 text-center">
            Belum ada alamat tersimpan.
        </p>
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogTitle class="om-page-title">
                {{ editing ? 'Ubah alamat' : 'Tambah alamat' }}
            </DialogTitle>
            <form class="flex flex-col gap-3.5" @submit.prevent="submit">
                <div class="grid grid-cols-2 gap-2.5">
                    <AuthTextField
                        id="first_name"
                        v-model="form.first_name"
                        label="Nama depan"
                        required
                        placeholder="Nama depan *"
                        :error="form.errors.first_name"
                    />
                    <AuthTextField
                        id="last_name"
                        v-model="form.last_name"
                        label="Nama belakang"
                        required
                        placeholder="Nama belakang *"
                        :error="form.errors.last_name"
                    />
                    <div class="col-span-2">
                        <AuthTextField
                            id="street_address"
                            v-model="form.street_address"
                            label="Alamat"
                            required
                            placeholder="Alamat lengkap *"
                            :error="form.errors.street_address"
                        />
                    </div>
                    <div class="col-span-2">
                        <AuthTextField
                            id="street_address_plus"
                            v-model="form.street_address_plus"
                            label="Detail (opsional)"
                            placeholder="Apartemen, blok, dll."
                        />
                    </div>
                    <AuthTextField
                        id="city"
                        v-model="form.city"
                        label="Kota"
                        required
                        placeholder="Kota *"
                        :error="form.errors.city"
                    />
                    <AuthTextField
                        id="postal_code"
                        v-model="form.postal_code"
                        label="Kode pos"
                        required
                        placeholder="Kode pos *"
                        :error="form.errors.postal_code"
                    />
                    <AuthTextField
                        id="state"
                        v-model="form.state"
                        label="Provinsi"
                        placeholder="Provinsi"
                    />
                    <AuthSelectField
                        id="country_id"
                        v-model="countryId"
                        label="Negara"
                        placeholder="Pilih negara"
                        :options="countryOptions"
                        :error="form.errors.country_id"
                    />
                    <div class="col-span-2">
                        <AuthTextField
                            id="phone_number"
                            v-model="form.phone_number"
                            label="No. HP"
                            type="tel"
                            placeholder="08xxxxxxxxxx"
                            :error="form.errors.phone_number"
                        />
                    </div>                    <fieldset class="col-span-2 space-y-2">
                        <legend class="om-label">Jenis alamat</legend>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label
                                class="flex items-center gap-2 text-[13px] text-zinc-700"
                            >
                                <input
                                    v-model="form.type"
                                    type="radio"
                                    :value="AddressType.SHIPPING"
                                    class="border-zinc-300 text-[var(--om-navy)] focus:ring-[var(--om-navy)]"
                                />
                                Pengiriman
                            </label>
                            <label
                                class="flex items-center gap-2 text-[13px] text-zinc-700"
                            >
                                <input
                                    v-model="form.type"
                                    type="radio"
                                    :value="AddressType.BILLING"
                                    class="border-zinc-300 text-[var(--om-navy)] focus:ring-[var(--om-navy)]"
                                />
                                Penagihan
                            </label>
                        </div>
                    </fieldset>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <button
                        type="button"
                        class="om-action-muted px-3"
                        @click="open = false"
                    >
                        Batal
                    </button>
                    <AuthSubmitButton
                        class="!w-auto px-5"
                        label="Simpan"
                        :enabled="canSaveAddress"
                        :processing="form.processing"
                    />
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
