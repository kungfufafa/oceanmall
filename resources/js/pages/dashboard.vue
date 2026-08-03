<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { MapPin, ShoppingBag, User } from 'lucide-vue-next';
import Card from '@/components/shop/card.vue';
import {
    addresses as accountAddresses,
    orders as accountOrders,
} from '@/routes/account';
import * as profile from '@/routes/profile';

const page = usePage();

const firstName =
    (page.props.auth.user as { first_name?: string; name?: string } | null)
        ?.first_name ??
    (page.props.auth.user as { name?: string } | null)?.name ??
    '';
</script>

<template>
    <Head title="Dashboard" />

    <h1 class="font-heading text-2xl font-bold text-zinc-900 dark:text-white">
        Welcome back<span v-if="firstName">, {{ firstName }}</span>
    </h1>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <Link :href="accountOrders.url()" class="block">
            <Card class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
                >
                    <ShoppingBag
                        class="size-5 text-zinc-600 dark:text-zinc-400"
                        aria-hidden="true"
                    />
                </div>
                <div>
                    <p
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        Orders
                    </p>
                    <p class="text-xs text-zinc-500">View your order history</p>
                </div>
            </Card>
        </Link>

        <Link :href="accountAddresses.url()" class="block">
            <Card class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
                >
                    <MapPin
                        class="size-5 text-zinc-600 dark:text-zinc-400"
                        aria-hidden="true"
                    />
                </div>
                <div>
                    <p
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        Addresses
                    </p>
                    <p class="text-xs text-zinc-500">Manage your addresses</p>
                </div>
            </Card>
        </Link>

        <Link :href="profile.edit.url()" class="block">
            <Card class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800"
                >
                    <User
                        class="size-5 text-zinc-600 dark:text-zinc-400"
                        aria-hidden="true"
                    />
                </div>
                <div>
                    <p
                        class="text-sm font-medium text-zinc-900 dark:text-white"
                    >
                        Profile
                    </p>
                    <p class="text-xs text-zinc-500">Manage your account</p>
                </div>
            </Card>
        </Link>
    </div>
</template>
