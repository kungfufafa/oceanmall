<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import EmptyState from '@/components/shop/empty-state.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
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
    if (!value) {
return '';
}

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
    router.post(
        accountNotifications.readAll.url(),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Notifikasi" />

    <div class="flex items-center justify-between gap-3 lg:hidden">
        <h1
            class="text-lg font-semibold tracking-tight text-foreground lg:hidden"
        >
            Notifikasi
        </h1>
        <Button
            v-if="notifications.data.some((n) => !n.read_at)"
            type="button"
            variant="ghost"
            class="om-action-muted h-auto p-0 !text-[12px]"
            @click="markAllRead"
        >
            Tandai semua dibaca
        </Button>
    </div>

    <div
        v-if="notifications.data.some((n) => !n.read_at)"
        class="mb-3 hidden justify-end lg:flex"
    >
        <Button
            type="button"
            variant="ghost"
            class="om-action-muted h-auto p-0 !text-[12px]"
            @click="markAllRead"
        >
            Tandai semua dibaca
        </Button>
    </div>

    <EmptyState
        v-if="notifications.data.length === 0"
        title="Belum ada notifikasi"
        description="Update pesanan akan muncul di sini."
        :icon="Bell"
    >
        <template #action>
            <Button as-child size="xl">
                <Link :href="shop.index.url()"> Belanja sekarang </Link>
            </Button>
        </template>
    </EmptyState>

    <Card v-else class="mt-2 gap-0 overflow-hidden py-0 shadow-none lg:mt-0">
        <CardContent class="flex flex-col gap-0 p-0">
            <Button
                v-for="item in notifications.data"
                :key="item.id"
                type="button"
                variant="ghost"
                :class="
                    cn(
                        'h-auto w-full justify-start gap-3 rounded-none px-3.5 py-3.5 text-left font-normal hover:bg-muted',
                        !item.read_at && 'bg-accent/60',
                    )
                "
                @click="markRead(item)"
            >
                <span
                    class="mt-1.5 size-2 shrink-0 rounded-full"
                    :class="item.read_at ? 'bg-transparent' : 'bg-primary'"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[13px] font-semibold text-foreground">
                            {{ item.title }}
                        </p>
                        <time
                            class="shrink-0 text-[11px] text-muted-foreground"
                            :datetime="item.created_at ?? undefined"
                        >
                            {{ formatDate(item.created_at) }}
                        </time>
                    </div>
                    <p
                        class="mt-0.5 text-[13px] leading-snug text-muted-foreground"
                    >
                        {{ item.body }}
                    </p>
                    <p
                        v-if="item.order_number"
                        class="mt-1 text-[12px] text-muted-foreground"
                    >
                        {{ item.order_number }}
                    </p>
                </div>
            </Button>
        </CardContent>
    </Card>
</template>
