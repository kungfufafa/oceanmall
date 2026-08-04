<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import accountNotifications from '@/routes/account/notifications';
import * as shop from '@/routes/shop';

type NotificationItem = {
    id: string;
    title: string;
    body: string;
    url: string;
    order_number: string | null;
    type: string | null;
    read_at: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    total: number;
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    notifications: Paginated<NotificationItem>;
}>();

function formatDate(value: string | null): string {
    if (!value) return '';
    return new Date(value).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function markRead(item: NotificationItem): void {
    if (!item.read_at) {
        router.post(
            accountNotifications.read.url(item.id),
            {},
            { preserveScroll: true },
        );
    }
    if (item.url) {
        router.visit(item.url);
    }
}

function markAllRead(): void {
    router.post(accountNotifications.readAll.url(), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifikasi" />

    <div class="flex items-center justify-between gap-3 lg:hidden">
        <h1 class="om-page-title !text-lg">Notifikasi</h1>
        <button
            v-if="notifications.data.some((n) => !n.read_at)"
            type="button"
            class="om-action-muted !text-[12px]"
            @click="markAllRead"
        >
            Tandai semua dibaca
        </button>
    </div>

    <div
        v-if="notifications.data.some((n) => !n.read_at)"
        class="mb-3 hidden justify-end lg:flex"
    >
        <button
            type="button"
            class="om-action-muted !text-[12px]"
            @click="markAllRead"
        >
            Tandai semua dibaca
        </button>
    </div>

    <div
        v-if="notifications.data.length === 0"
        class="flex flex-col items-center py-16 text-center"
    >
        <div
            class="flex size-14 items-center justify-center rounded-full bg-zinc-100"
        >
            <Bell class="size-6 text-zinc-400" stroke-width="1.75" />
        </div>
        <h2 class="om-page-title mt-4">Belum ada notifikasi</h2>
        <p class="om-meta mt-1">Update pesanan akan muncul di sini.</p>
        <Button as-child size="xl" class="mt-5">
            <Link :href="shop.index.url()"> Belanja sekarang </Link>
        </Button>
    </div>

    <ul v-else role="list" class="mt-2 divide-y divide-zinc-100 lg:mt-0">
        <li v-for="item in notifications.data" :key="item.id">
            <button
                type="button"
                class="flex w-full gap-3 py-3.5 text-left transition hover:bg-zinc-50"
                :class="!item.read_at ? 'bg-sky-50/60' : ''"
                @click="markRead(item)"
            >
                <span
                    class="mt-1.5 size-2 shrink-0 rounded-full"
                    :class="
                        item.read_at ? 'bg-transparent' : 'bg-[var(--om-navy)]'
                    "
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[13px] font-semibold text-zinc-900">
                            {{ item.title }}
                        </p>
                        <time
                            class="shrink-0 text-[11px] text-zinc-400"
                            :datetime="item.created_at ?? undefined"
                        >
                            {{ formatDate(item.created_at) }}
                        </time>
                    </div>
                    <p class="mt-0.5 text-[13px] leading-snug text-zinc-600">
                        {{ item.body }}
                    </p>
                    <p
                        v-if="item.order_number"
                        class="mt-1 text-[12px] text-zinc-400"
                    >
                        {{ item.order_number }}
                    </p>
                </div>
            </button>
        </li>
    </ul>
</template>
