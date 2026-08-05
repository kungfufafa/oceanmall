<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { home } from '@/routes';

withDefaults(
    defineProps<{
        title: string;
        backHref?: string;
        /** Primary navy link (e.g. Daftar) or muted (e.g. Kosongkan) */
        endTone?: 'primary' | 'muted';
        endLabel?: string;
        endHref?: string;
        maxWidthClass?: string;
    }>(),
    {
        backHref: undefined,
        endTone: 'primary',
        endLabel: undefined,
        endHref: undefined,
        maxWidthClass: 'max-w-7xl',
    },
);

const emit = defineEmits<{
    endClick: [];
}>();
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-border bg-background">
        <div
            :class="[
                'mx-auto grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 px-4',
                maxWidthClass,
            ]"
            :style="{ height: 'var(--om-header-height)' }"
        >
            <div class="justify-self-start">
                <Link
                    :href="backHref ?? home.url()"
                    class="flex size-11 items-center justify-center text-[var(--om-navy)]"
                    aria-label="Kembali"
                >
                    <ChevronLeft class="size-5" stroke-width="2.25" />
                </Link>
            </div>

            <h1 class="om-page-title truncate text-center whitespace-nowrap">
                <slot name="title">{{ title }}</slot>
            </h1>

            <div class="flex min-h-11 items-center justify-self-end">
                <slot name="end">
                    <Link
                        v-if="endLabel && endHref"
                        :href="endHref"
                        class="flex h-11 items-center px-2"
                        :class="
                            endTone === 'muted'
                                ? 'om-action-muted'
                                : 'om-action-primary'
                        "
                    >
                        {{ endLabel }}
                    </Link>
                    <button
                        v-else-if="endLabel"
                        type="button"
                        class="flex h-11 items-center px-2"
                        :class="
                            endTone === 'muted'
                                ? 'om-action-muted'
                                : 'om-action-primary'
                        "
                        @click="emit('endClick')"
                    >
                        {{ endLabel }}
                    </button>
                    <span v-else class="block size-11" aria-hidden="true" />
                </slot>
            </div>
        </div>
    </header>
</template>
