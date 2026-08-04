<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useShop } from '@/composables/useShop';
import type { CountryByZoneData } from '@/types/shop';

const props = withDefaults(
    defineProps<{
        appearance?: 'default' | 'on-navy';
    }>(),
    { appearance: 'default' },
);

const { zone, availableZones, changeZone } = useShop();

const open = ref<boolean>(false);

watch(
    [zone, availableZones],
    ([currentZone, zones]) => {
        if (!currentZone && zones.length > 1) {
            open.value = true;
        }
    },
    { immediate: true },
);

const grouped = computed<Record<string, CountryByZoneData[]>>(() => {
    return availableZones.value.reduce<Record<string, CountryByZoneData[]>>(
        (acc, country) => {
            const key = country.zoneName;
            (acc[key] ??= []).push(country);
            return acc;
        },
        {},
    );
});

const currentCountry = computed<CountryByZoneData | undefined>(() =>
    availableZones.value.find(
        (c) => c.countryCode === zone.value?.country_code,
    ),
);

const onNavy = computed(() => props.appearance === 'on-navy');

function select(country: CountryByZoneData): void {
    open.value = false;
    changeZone(country.countryCode);
}
</script>

<template>
    <div>
        <button
            v-if="onNavy"
            type="button"
            class="inline-flex max-w-xs items-center gap-2 text-left text-sm text-white/90 transition hover:text-white"
            @click="open = true"
        >
            <img
                v-if="currentCountry"
                :src="currentCountry.countryFlag"
                alt=""
                class="block h-auto w-4 shrink-0 rounded-sm"
            />
            <span class="truncate">
                <template v-if="zone">
                    Dikirim ke
                    <span class="font-semibold underline underline-offset-2">{{
                        zone.country_name
                    }}</span>
                </template>
                <template v-else>
                    Pilih lokasi pengiriman
                </template>
            </span>
        </button>

        <div v-else-if="zone" class="flex items-center">
            <p class="text-sm/5 text-zinc-700">Dikirim ke:</p>
            <button
                type="button"
                class="group ml-4 flex items-center font-medium hover:text-[var(--om-navy)]"
                @click="open = true"
            >
                <img
                    v-if="currentCountry"
                    :src="currentCountry.countryFlag"
                    alt=""
                    class="block h-auto w-5 shrink-0"
                />
                <span class="ml-2 block text-sm font-medium underline">{{
                    zone.country_name
                }}</span>
            </button>
        </div>

        <Dialog v-model:open="open">
            <DialogContent
                class="border-0 bg-transparent p-0 shadow-none sm:max-w-lg"
            >
                <div
                    class="space-y-4 rounded-md border border-zinc-200 bg-white p-4"
                >
                    <DialogTitle class="om-page-title !text-lg"
                        >Pilih negara</DialogTitle
                    >

                    <DialogDescription
                        v-if="zone"
                        class="text-sm text-zinc-600"
                    >
                        Saat ini dikirim ke:
                        <span class="font-semibold text-[var(--om-navy)]">{{
                            zone.country_name
                        }}</span>
                    </DialogDescription>

                    <DialogDescription class="text-sm text-zinc-600">
                        Mengganti negara dapat mengubah harga dan mata uang.
                    </DialogDescription>

                    <div
                        class="mt-4 max-h-96 divide-y divide-zinc-200 overflow-y-auto"
                    >
                        <div
                            v-for="(countries, zoneName) in grouped"
                            :key="zoneName"
                            class="py-4"
                        >
                            <h4
                                class="text-sm font-medium text-[var(--om-navy)]"
                            >
                                {{ zoneName }}
                            </h4>
                            <ul role="listbox" class="mt-2 space-y-1">
                                <li
                                    v-for="country in countries"
                                    :key="country.countryId"
                                >
                                    <button
                                        type="button"
                                        :class="[
                                            'flex w-full items-center rounded-md px-3 py-2 text-sm transition',
                                            zone?.country_code ===
                                            country.countryCode
                                                ? 'bg-zinc-100 font-medium text-[var(--om-navy)]'
                                                : 'text-zinc-600 hover:bg-zinc-50',
                                        ]"
                                        @click="select(country)"
                                    >
                                        <img
                                            :src="country.countryFlag"
                                            alt=""
                                            class="block h-auto w-5 shrink-0 rounded-xs"
                                        />
                                        <span class="ml-2">{{
                                            country.countryName
                                        }}</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
