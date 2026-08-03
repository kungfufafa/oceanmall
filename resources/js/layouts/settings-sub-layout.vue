<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import * as appearance from '@/routes/appearance';
import * as profile from '@/routes/profile';
import * as security from '@/routes/security';

type Item = { href: string; label: string };

const page = usePage();

const items = computed<Item[]>(() => [
    { href: profile.edit.url(), label: 'Profile' },
    { href: security.edit.url(), label: 'Security' },
    { href: appearance.edit.url(), label: 'Appearance' },
]);

function isActive(item: Item): boolean {
    const path = (page.url ?? '').split('?')[0];
    return path.startsWith(item.href);
}
</script>

<template>
    <section class="w-full">
        <div class="relative mb-6 w-full">
            <h1
                class="font-heading text-2xl font-bold text-zinc-900 dark:text-white"
            >
                Settings
            </h1>
            <p class="mt-1 mb-6 text-base text-zinc-500">
                Manage your profile and account settings
            </p>
            <hr class="border-zinc-200/60 dark:border-zinc-700/60" />
        </div>

        <div class="flex items-start max-md:flex-col">
            <div class="me-10 w-full pb-4 md:w-[220px]">
                <nav class="flex flex-col gap-1" :aria-label="'Settings'">
                    <Link
                        v-for="item in items"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'rounded-md px-3 py-2 text-sm transition',
                            isActive(item)
                                ? 'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white'
                                : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>

            <hr class="border-zinc-200/60 md:hidden dark:border-zinc-700/60" />

            <div class="flex-1 self-stretch max-md:pt-6">
                <slot />
            </div>
        </div>
    </section>
</template>
