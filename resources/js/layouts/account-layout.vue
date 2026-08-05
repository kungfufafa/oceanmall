<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppPageHeader from '@/components/shop/app-page-header.vue';
import Container from '@/components/shop/container.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { dashboard, logout } from '@/routes';
import {
    addresses as accountAddresses,
    notifications as accountNotifications,
    orders as accountOrders,
} from '@/routes/account';
import * as profile from '@/routes/profile';
import * as security from '@/routes/security';

type NavItem = { href: string; label: string; match: (path: string) => boolean };

const page = usePage();

const path = computed(() => (page.url ?? '').split('?')[0] || '/');

const isSettings = computed(() => path.value.startsWith('/settings'));

const headerTitle = computed(() => {
    if (path.value.startsWith('/account/notifications')) return 'Notifikasi';
    if (path.value.startsWith('/account/orders')) return 'Pesanan';
    if (path.value.startsWith('/account/addresses')) return 'Alamat';
    if (path.value.startsWith('/settings/security')) return 'Keamanan';
    if (path.value.startsWith('/settings')) return 'Profil';
    return 'Akun';
});

const items = computed<NavItem[]>(() => [
    {
        href: dashboard.url(),
        label: 'Ringkasan',
        match: (p) => p === '/dashboard' || p === '/dashboard/',
    },
    {
        href: accountOrders.url(),
        label: 'Pesanan',
        match: (p) => p.startsWith('/account/orders'),
    },
    {
        href: accountNotifications.url(),
        label: 'Notifikasi',
        match: (p) => p.startsWith('/account/notifications'),
    },
    {
        href: accountAddresses.url(),
        label: 'Alamat',
        match: (p) => p.startsWith('/account/addresses'),
    },
    {
        href: profile.edit.url(),
        label: 'Profil',
        match: (p) => p.startsWith('/settings'),
    },
]);

const settingsItems = computed<NavItem[]>(() => [
    {
        href: profile.edit.url(),
        label: 'Profil',
        match: (p) => p.startsWith('/settings/profile'),
    },
    {
        href: security.edit.url(),
        label: 'Keamanan',
        match: (p) => p.startsWith('/settings/security'),
    },
]);

function isActive(item: NavItem): boolean {
    return item.match(path.value);
}

function onLogout(): void {
    router.post(logout.url());
}
</script>

<template>
    <AppPageHeader
        class="lg:hidden"
        :title="headerTitle"
        end-label="Keluar"
        end-tone="muted"
        max-width-class="max-w-7xl"
        @end-click="onLogout"
    />

    <Container class="py-4 lg:py-10">
            <div class="grid grid-cols-1 lg:grid-cols-5 lg:gap-x-12">
                <aside class="lg:col-span-1">
                    <div class="mb-4 hidden items-center justify-between lg:flex">
                        <h2 class="text-lg font-semibold tracking-tight text-foreground">
                            Akun saya
                        </h2>
                    </div>

                    <!-- Mobile chips -->
                    <nav
                        role="navigation"
                        class="mb-3 flex gap-1 overflow-x-auto lg:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        aria-label="Menu akun"
                    >
                        <Link
                            v-for="item in items"
                            :key="item.href"
                            :href="item.href"
                            :class="cn(
                                'shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition',
                                isActive(item)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground',
                            )"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <nav
                        v-if="isSettings"
                        role="navigation"
                        class="mb-4 flex gap-1 overflow-x-auto lg:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                        aria-label="Pengaturan"
                    >
                        <Link
                            v-for="item in settingsItems"
                            :key="`m-${item.href}`"
                            :href="item.href"
                            :class="cn(
                                'shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition',
                                isActive(item)
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground',
                            )"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <!-- Desktop sidebar -->
                    <nav
                        role="navigation"
                        class="mt-1 hidden flex-col gap-1 lg:flex"
                        aria-label="Menu akun"
                    >
                        <Link
                            v-for="item in items"
                            :key="`d-${item.href}`"
                            :href="item.href"
                            :class="cn(
                                'rounded-md px-3 py-2 text-sm transition',
                                isActive(item)
                                    ? 'bg-muted font-semibold text-primary'
                                    : 'text-muted-foreground hover:bg-muted',
                            )"
                        >
                            {{ item.label }}
                        </Link>

                        <Separator v-if="isSettings" class="my-2" />

                        <template v-if="isSettings">
                            <p
                                class="px-3 pt-1 pb-1.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Pengaturan
                            </p>
                            <Link
                                v-for="item in settingsItems"
                                :key="`ds-${item.href}`"
                                :href="item.href"
                                :class="cn(
                                    'rounded-md px-3 py-2 text-sm transition',
                                    isActive(item)
                                        ? 'bg-muted font-semibold text-primary'
                                        : 'text-muted-foreground hover:bg-muted',
                                )"
                            >
                                {{ item.label }}
                            </Link>
                        </template>

                        <Button
                            type="button"
                            variant="ghost"
                            class="mt-3 justify-start px-3 text-destructive hover:bg-destructive/10 hover:text-destructive"
                            @click="onLogout"
                        >
                            Keluar
                        </Button>
                    </nav>
                </aside>

                <div class="lg:col-span-4">
                    <div
                        class="mb-4 hidden items-center justify-between lg:flex"
                    >
                        <h1 class="text-lg font-semibold tracking-tight text-foreground">
                            {{ headerTitle }}
                        </h1>
                    </div>
                    <slot />
                </div>
            </div>
    </Container>
</template>
