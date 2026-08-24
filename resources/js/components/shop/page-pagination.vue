<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

defineProps<{
    links: PageLink[];
    class?: string;
}>();
</script>

<template>
    <nav
        v-if="links.length > 3"
        :class="cn('mt-6 flex flex-wrap justify-center gap-1', $props.class)"
        aria-label="Halaman"
    >
        <template v-for="link in links" :key="`${link.label}-${link.url}`">
            <Button
                v-if="link.url"
                as-child
                variant="ghost"
                size="sm"
                :class="
                    cn(
                        'min-w-9 px-2.5',
                        link.active &&
                            'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground',
                    )
                "
            >
                <Link :href="link.url">
                    <span v-html="link.label" />
                </Link>
            </Button>
            <Button
                v-else
                type="button"
                variant="ghost"
                size="sm"
                class="min-w-9 px-2.5 opacity-40"
                disabled
            >
                <span v-html="link.label" />
            </Button>
        </template>
    </nav>
</template>
