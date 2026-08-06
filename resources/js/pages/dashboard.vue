<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, ChevronRight, MapPin, ShoppingBag, User } from 'lucide-vue-next';
import OrderStatusBadge from '@/components/account/order-status-badge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/format';
import {
    addresses as accountAddresses,
    notifications as accountNotifications,
    orders as accountOrders,
} from '@/routes/account';
import { show as accountOrderShow } from '@/routes/account/orders';
import * as profile from '@/routes/profile';
import * as shop from '@/routes/shop';

type RecentOrder = {
    id: number;
    number: string;
    price_amount: number;
    currency_code: string;
    status: string;
    payment_status: string;
    created_at: string | null;
};

const props = defineProps<{
    recentOrders: RecentOrder[];
    unreadNotifications: number;
}>();

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

function formatDate(value: string | null): string {
    if (!value) return '';
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Akun" />

    <p class="text-sm text-muted-foreground">
        Halo<span v-if="firstName">, {{ firstName }}</span
        >. Kelola pesanan dan akunmu di sini.
    </p>

    <div v-if="unreadNotifications > 0" class="mt-3">
        <Button as-child variant="outline" size="sm" class="h-8 gap-1.5">
            <Link :href="accountNotifications.url()">
                <Bell class="size-3.5" aria-hidden="true" />
                {{ unreadNotifications }} notifikasi belum dibaca
            </Link>
        </Button>
    </div>

    <Card class="mt-4 gap-0 overflow-hidden py-0 shadow-none">
        <div
            class="flex items-center justify-between gap-3 border-b border-border px-4 py-3.5"
        >
            <div>
                <h3 class="text-sm font-semibold text-foreground">
                    Pesanan terbaru
                </h3>
                <p class="text-xs text-muted-foreground">
                    Lanjutkan pantau status atau belanja lagi
                </p>
            </div>
            <Button
                as-child
                variant="ghost"
                size="sm"
                class="h-8 shrink-0 text-xs font-medium"
            >
                <Link :href="accountOrders.url()">Semua</Link>
            </Button>
        </div>
        <CardContent class="flex flex-col gap-0 p-0">
            <template v-if="recentOrders.length">
                <Link
                    v-for="order in recentOrders"
                    :key="order.id"
                    :href="accountOrderShow.url(order.id)"
                    class="flex items-center gap-3 border-b border-border px-3.5 py-3 transition last:border-b-0 hover:bg-muted"
                >
                    <span class="min-w-0 flex-1">
                        <span
                            class="flex flex-wrap items-center gap-2 text-[13px] font-semibold text-foreground"
                        >
                            #{{ order.number }}
                            <OrderStatusBadge
                                :status="order.payment_status || order.status"
                            />
                        </span>
                        <span
                            class="mt-0.5 block text-[12px] text-muted-foreground"
                        >
                            {{ formatDate(order.created_at) }}
                            ·
                            {{
                                formatMoney(
                                    order.price_amount,
                                    order.currency_code || 'IDR',
                                )
                            }}
                        </span>
                    </span>
                    <ChevronRight
                        class="size-4 shrink-0 text-muted-foreground/50"
                        aria-hidden="true"
                    />
                </Link>
            </template>
            <div v-else class="flex flex-col items-start gap-3 px-3.5 py-5">
                <p class="text-sm text-muted-foreground">
                    Belum ada pesanan. Yuk mulai belanja.
                </p>
                <Button as-child size="sm">
                    <Link :href="shop.index.url()">Belanja sekarang</Link>
                </Button>
            </div>
        </CardContent>
    </Card>

    <Card class="mt-4 gap-0 overflow-hidden py-0 shadow-none">
        <CardContent class="flex flex-col gap-0 p-0">
            <Link
                v-for="item in shortcuts"
                :key="item.href"
                :href="item.href"
                class="flex items-center gap-3 border-b border-border px-3.5 py-3.5 transition last:border-b-0 hover:bg-muted"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted text-primary"
                >
                    <component
                        :is="item.icon"
                        class="size-5"
                        stroke-width="1.75"
                        aria-hidden="true"
                    />
                </span>
                <span class="min-w-0 flex-1">
                    <span
                        class="block text-[13px] font-semibold text-foreground"
                        >{{ item.label }}</span
                    >
                    <span class="mt-0.5 block text-sm text-muted-foreground">{{
                        item.hint
                    }}</span>
                </span>
                <ChevronRight
                    class="size-4 shrink-0 text-muted-foreground/50"
                    aria-hidden="true"
                />
            </Link>
        </CardContent>
    </Card>
</template>
