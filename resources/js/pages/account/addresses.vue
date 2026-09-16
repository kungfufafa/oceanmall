<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Check,
    CreditCard,
    MapPin,
    MoreHorizontal,
    Truck,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AddressController from '@/actions/App/Http/Controllers/Account/AddressController';
import AuthSelectField from '@/components/auth/auth-select-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import EmptyState from '@/components/shop/empty-state.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { AddressType } from '@/types/shop';
import type { Address } from '@/types/shop';

type CountryOption = { id: number; name: string; cca2: string };

type BookAddress = Address & {
    rajaongkir_destination_id?: string | null;
    rajaongkir_destination_label?: string | null;
    rajaongkir_pin_point?: string | null;
};

type DestinationResult = {
    id: string | number;
    label: string;
    province_name?: string | null;
    city_name?: string | null;
    zip_code?: string | null;
};

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
    rajaongkir_destination_id: string;
    rajaongkir_destination_label: string;
    rajaongkir_pin_point: string;
};

const props = defineProps<{
    addresses: BookAddress[];
    countries: CountryOption[];
    komerceEnabled?: boolean;
}>();

const editing = ref<BookAddress | null>(null);
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
    rajaongkir_destination_id: '',
    rajaongkir_destination_label: '',
    rajaongkir_pin_point: '',
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
    destinationQuery.value = '';
    destinationResults.value = [];
    pinPointError.value = null;
    open.value = true;
}

function startEdit(address: BookAddress): void {
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
    form.rajaongkir_destination_id = address.rajaongkir_destination_id ?? '';
    form.rajaongkir_destination_label =
        address.rajaongkir_destination_label ?? '';
    form.rajaongkir_pin_point = address.rajaongkir_pin_point ?? '';
    destinationQuery.value = address.rajaongkir_destination_label ?? '';
    open.value = true;
}

const destinationQuery = ref('');
const destinationResults = ref<DestinationResult[]>([]);
const destinationSearching = ref(false);
const destinationSearchError = ref<string | null>(null);
const pinPointLocating = ref(false);
const pinPointError = ref<string | null>(null);
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

    if (
        form.rajaongkir_destination_id &&
        value === form.rajaongkir_destination_label
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
        destinationSearchError.value = 'Tidak dapat mencari tujuan saat ini.';
    } finally {
        destinationSearching.value = false;
    }
}

function selectDestination(result: DestinationResult): void {
    form.rajaongkir_destination_id = String(result.id);
    form.rajaongkir_destination_label = result.label;
    destinationQuery.value = result.label;
    destinationResults.value = [];

    if (result.province_name) {
        form.state = result.province_name;
    }

    if (result.city_name) {
        form.city = result.city_name;
    }

    if (result.zip_code) {
        form.postal_code = result.zip_code;
    }
}

function clearDestination(): void {
    form.rajaongkir_destination_id = '';
    form.rajaongkir_destination_label = '';
    destinationQuery.value = '';
    destinationResults.value = [];
}

function useCurrentLocation(): void {
    pinPointError.value = null;

    if (!('geolocation' in navigator)) {
        pinPointError.value =
            'Browser tidak mendukung deteksi lokasi. Isi koordinat secara manual.';

        return;
    }

    pinPointLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.rajaongkir_pin_point = `${position.coords.latitude.toFixed(6)},${position.coords.longitude.toFixed(6)}`;
            pinPointLocating.value = false;
        },
        () => {
            pinPointError.value =
                'Tidak bisa mengambil lokasi. Izinkan akses lokasi atau isi koordinat manual.';
            pinPointLocating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
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
    if (!window.confirm('Yakin ingin menghapus alamat ini?')) {
return;
}

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
        <p class="text-sm text-muted-foreground">
            Alamat pengiriman & penagihan
        </p>
        <Button
            type="button"
            variant="link"
            class="om-action-primary h-auto p-0"
            @click="startCreate"
        >
            Tambah
        </Button>
    </div>

    <div class="mt-4 flex flex-col gap-3">
        <div v-if="addresses.length" class="flex flex-col gap-3">
            <Card
                v-for="address in addresses"
                :key="address.id"
                class="gap-0 overflow-hidden py-0 shadow-none"
            >
                <div
                    class="flex items-center justify-between gap-2 border-b border-border bg-muted/30 px-3.5 py-2.5"
                >
                    <h3 class="text-sm font-semibold text-foreground">
                        {{ address.first_name }} {{ address.last_name }}
                    </h3>
                    <Badge
                        v-if="address.type === AddressType.BILLING"
                        variant="secondary"
                        class="text-[11px]"
                    >
                        Penagihan
                    </Badge>
                </div>

                <CardContent class="p-3.5 pt-3">
                    <address
                        class="text-sm leading-5 text-muted-foreground not-italic"
                    >
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
                        <Badge
                            v-if="address.shipping_default"
                            variant="secondary"
                            class="text-[11px]"
                        >
                            <Check aria-hidden="true" />
                            Default kirim
                        </Badge>
                        <Badge
                            v-if="address.billing_default"
                            variant="secondary"
                            class="text-[11px]"
                        >
                            <Check aria-hidden="true" />
                            Default tagih
                        </Badge>
                        <Badge
                            v-if="address.rajaongkir_destination_label"
                            variant="outline"
                            class="text-[11px]"
                        >
                            {{ address.rajaongkir_destination_label }}
                        </Badge>
                        <Badge
                            v-if="address.rajaongkir_pin_point"
                            variant="outline"
                            class="text-[11px]"
                        >
                            Pin {{ address.rajaongkir_pin_point }}
                        </Badge>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            class="px-3 text-[12px]"
                            @click="startEdit(address)"
                        >
                            Ubah
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            class="px-3 text-[12px] text-destructive hover:bg-destructive/10 hover:text-destructive"
                            @click="destroy(address)"
                        >
                            Hapus
                        </Button>
                        <DropdownMenu
                            v-if="
                                !address.shipping_default ||
                                !address.billing_default
                            "
                        >
                            <DropdownMenuTrigger as-child>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="size-9"
                                    aria-label="Lainnya"
                                >
                                    <MoreHorizontal aria-hidden="true" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem
                                    v-if="!address.shipping_default"
                                    @click="setDefaultShipping(address)"
                                >
                                    <Truck aria-hidden="true" />
                                    Jadikan default kirim
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    v-if="!address.billing_default"
                                    @click="setDefaultBilling(address)"
                                >
                                    <CreditCard aria-hidden="true" />
                                    Jadikan default tagih
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </CardContent>
            </Card>
        </div>

        <EmptyState
            v-else
            title="Belum ada alamat tersimpan"
            description="Tambahkan alamat pengiriman atau penagihan untuk checkout."
            :icon="MapPin"
            class="py-8"
        />
    </div>

    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogTitle>
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
                    </div>
                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label for="destination_search">
                            Kecamatan pengiriman
                        </Label>
                        <div class="relative">
                            <Input
                                id="destination_search"
                                v-model="destinationQuery"
                                type="search"
                                autocomplete="off"
                                class="h-[var(--om-control-height)] w-full pr-14 text-[13px] [&::-webkit-search-cancel-button]:hidden"
                                placeholder="Contoh: Kebayoran Baru"
                                @focus="
                                    destinationQuery.trim().length >= 2 &&
                                    searchDestinations(destinationQuery.trim())
                                "
                            />
                            <Button
                                v-if="form.rajaongkir_destination_id"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="absolute inset-y-0 right-2 h-auto px-2 text-xs text-muted-foreground"
                                @click="clearDestination"
                            >
                                Ganti
                            </Button>
                            <Card
                                v-if="destinationResults.length"
                                class="absolute z-20 mt-1 max-h-56 w-full gap-0 overflow-auto rounded-md py-0 shadow-sm"
                            >
                                <CardContent class="p-0">
                                    <Button
                                        v-for="result in destinationResults"
                                        :key="result.id"
                                        type="button"
                                        variant="ghost"
                                        class="h-auto w-full justify-start rounded-none px-3 py-2.5 text-left text-[13px] font-normal"
                                        @click="selectDestination(result)"
                                    >
                                        {{ result.label }}
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
                        <p
                            v-if="destinationSearching"
                            class="text-xs text-muted-foreground"
                        >
                            Mencari…
                        </p>
                        <p
                            v-else-if="destinationSearchError"
                            class="text-xs text-destructive"
                        >
                            {{ destinationSearchError }}
                        </p>
                        <p
                            v-else-if="form.rajaongkir_destination_label"
                            class="text-xs text-muted-foreground"
                        >
                            {{ form.rajaongkir_destination_label }}
                        </p>
                    </div>
                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label for="rajaongkir_pin_point">Pinpoint lokasi</Label>
                        <div class="flex gap-2">
                            <Input
                                id="rajaongkir_pin_point"
                                v-model="form.rajaongkir_pin_point"
                                type="text"
                                autocomplete="off"
                                inputmode="decimal"
                                class="h-[var(--om-control-height)] w-full text-[13px]"
                                placeholder="-6.2380,106.7830"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                class="h-[var(--om-control-height)] shrink-0 px-3 text-xs"
                                :disabled="pinPointLocating"
                                @click="useCurrentLocation"
                            >
                                {{
                                    pinPointLocating
                                        ? 'Mencari…'
                                        : 'Gunakan lokasi saya'
                                }}
                            </Button>
                        </div>
                        <p
                            v-if="pinPointError"
                            class="text-xs text-destructive"
                        >
                            {{ pinPointError }}
                        </p>
                    </div>
                    <fieldset class="col-span-2 flex flex-col gap-2">
                        <legend class="text-sm font-medium text-foreground">
                            Jenis alamat
                        </legend>
                        <RadioGroup
                            v-model="form.type"
                            class="flex flex-wrap gap-4 pt-1"
                        >
                            <div class="flex items-center gap-2">
                                <RadioGroupItem
                                    :id="`type-shipping`"
                                    :value="AddressType.SHIPPING"
                                />
                                <Label
                                    :for="`type-shipping`"
                                    class="text-[13px] font-normal text-foreground"
                                >
                                    Pengiriman
                                </Label>
                            </div>
                            <div class="flex items-center gap-2">
                                <RadioGroupItem
                                    :id="`type-billing`"
                                    :value="AddressType.BILLING"
                                />
                                <Label
                                    :for="`type-billing`"
                                    class="text-[13px] font-normal text-foreground"
                                >
                                    Penagihan
                                </Label>
                            </div>
                        </RadioGroup>
                    </fieldset>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <Button
                        type="button"
                        variant="ghost"
                        class="om-action-muted px-3"
                        @click="open = false"
                    >
                        Batal
                    </Button>
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
