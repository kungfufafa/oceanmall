<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const model = defineModel<string>({ default: '' });

withDefaults(
    defineProps<{
        placeholder?: string;
        id?: string;
        class?: string;
    }>(),
    {
        placeholder: 'Cari…',
        id: undefined,
        class: undefined,
    },
);
</script>

<template>
    <div :class="cn('relative', $props.class)">
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            aria-hidden="true"
        />
        <Input
            :id="id"
            v-model="model"
            type="search"
            :placeholder="placeholder"
            class="h-[var(--om-control-height)] border-border bg-background pr-9 pl-9 text-[13px] placeholder:text-muted-foreground focus-visible:border-primary [&::-webkit-search-cancel-button]:hidden"
        />
        <Button
            v-if="model"
            type="button"
            variant="ghost"
            size="icon-sm"
            class="absolute top-1/2 right-1.5 -translate-y-1/2 text-muted-foreground"
            aria-label="Hapus pencarian"
            @click="model = ''"
        >
            <X />
        </Button>
    </div>
</template>
