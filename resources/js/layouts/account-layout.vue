<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Container from '@/components/shop/container.vue';
import { dashboard, logout } from '@/routes';
import {
    addresses as accountAddresses,
    orders as accountOrders,
} from '@/routes/account';
import * as profile from '@/routes/profile';

type NavItem = { href: string; label: string; exact?: boolean };

const page = usePage();

const items = computed<NavItem[]>(() => [
    { href: dashboard.url(), label: 'Overview', exact: true },
    { href: accountOrders.url(), label: 'Orders' },
    { href: accountAddresses.url(), label: 'Addresses' },
    { href: profile.edit.url(), label: 'Profile' },
]);

function isActive(item: NavItem): boolean {
    const path = (page.url ?? '').split('?')[0];
    return item.exact ? path === item.href : path.startsWith(item.href);
}
</script>

<template>
    <Container class="py-10 sm:py-16 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-5 lg:gap-x-12">
            <div class="lg:col-span-1">
                <h2
                    class="hidden font-heading text-xl leading-6 font-medium text-zinc-900 lg:block dark:text-white"
                >
                    My account
                </h2>

                <nav
                    role="navigation"
                    class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:hidden"
                >
                    <Link
                        v-for="item in items"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'inline-block text-sm hover:underline hover:decoration-2',
                            isActive(item)
                                ? 'font-medium text-zinc-900 dark:text-white'
                                : 'text-zinc-500',
                        ]"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <nav
                    role="navigation"
                    class="mt-10 hidden flex-col space-y-4 lg:flex"
                >
                    <Link
                        v-for="item in items"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'inline-block text-sm hover:underline hover:decoration-2',
                            isActive(item)
                                ? 'font-medium text-zinc-900 dark:text-white'
                                : 'text-zinc-500',
                        ]"
                    >
                        {{ item.label }}
                    </Link>

                    <form method="POST" :action="logout.url()">
                        <button
                            type="submit"
                            class="text-sm text-red-600 transition hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Log out
                        </button>
                    </form>
                </nav>
            </div>

            <div class="lg:col-span-4">
                <slot />
            </div>
        </div>
    </Container>
</template>
