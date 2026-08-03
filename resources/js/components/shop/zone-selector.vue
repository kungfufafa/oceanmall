<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Card from '@/components/shop/card.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { useShop } from '@/composables/useShop';
import type { CountryByZoneData } from '@/types/shop';

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

function select(country: CountryByZoneData): void {
    open.value = false;
    changeZone(country.countryCode);
}
</script>

<template>
    <div>
        <div v-if="zone" class="flex items-center">
            <p class="text-sm/5 text-zinc-700 dark:text-zinc-300">
                Shipping to :
            </p>
            <button
                type="button"
                class="group ml-4 flex items-center font-medium hover:text-zinc-950 dark:hover:text-white"
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
                <Card class="space-y-4">
                    <DialogTitle
                        class="font-heading text-lg font-semibold text-zinc-900 dark:text-white"
                        >Select your country</DialogTitle
                    >

                    <DialogDescription
                        v-if="zone"
                        class="text-sm text-zinc-600 dark:text-zinc-400"
                    >
                        Currently shipping to:
                        <span
                            class="font-semibold text-zinc-900 dark:text-white"
                            >{{ zone.country_name }}</span
                        >
                    </DialogDescription>

                    <DialogDescription
                        class="text-sm text-zinc-600 dark:text-zinc-400"
                    >
                        Changing your country may update prices and currency.
                    </DialogDescription>

                    <div
                        class="mt-4 max-h-96 divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700"
                    >
                        <div
                            v-for="(countries, zoneName) in grouped"
                            :key="zoneName"
                            class="py-4"
                        >
                            <h4
                                class="text-sm font-medium text-zinc-900 dark:text-white"
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
                                            'flex w-full items-center rounded-lg px-3 py-2 text-sm transition',
                                            zone?.country_code ===
                                            country.countryCode
                                                ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                                : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800/50',
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
                </Card>
            </DialogContent>
        </Dialog>
    </div>
</template>
