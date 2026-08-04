<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronRight, MapPin, ShoppingBag, User } from 'lucide-vue-next';
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

const shortcuts = [
    {
        href: accountOrders.url(),
        label: 'Pesanan',
        hint: 'Lihat riwayat belanja',
        icon: ShoppingBag,
    },
    {
        href: accountAddresses.url(),
        label: 'Alamat',
        hint: 'Kelola alamat pengiriman',
        icon: MapPin,
    },
    {
        href: profile.edit.url(),
        label: 'Profil',
        hint: 'Data akun & email',
        icon: User,
    },
] as const;
</script>

<template>
    <Head title="Akun" />

    <p class="om-meta">
        Halo<span v-if="firstName">, {{ firstName }}</span
        >. Kelola pesanan dan akunmu di sini.
    </p>

    <ul class="mt-4 divide-y divide-zinc-100 overflow-hidden rounded-md border border-zinc-200 bg-white">
        <li v-for="item in shortcuts" :key="item.href">
            <Link
                :href="item.href"
                class="flex items-center gap-3 px-3.5 py-3.5 transition hover:bg-zinc-50"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[var(--om-navy)]"
                >
                    <component
                        :is="item.icon"
                        class="size-5"
                        stroke-width="1.75"
                        aria-hidden="true"
                    />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[13px] font-semibold text-zinc-900">{{
                        item.label
                    }}</span>
                    <span class="om-meta mt-0.5 block">{{ item.hint }}</span>
                </span>
                <ChevronRight
                    class="size-4 shrink-0 text-zinc-300"
                    aria-hidden="true"
                />
            </Link>
        </li>
    </ul>
</template>
