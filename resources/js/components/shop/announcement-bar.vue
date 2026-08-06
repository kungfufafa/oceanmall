<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import Container from './container.vue';

defineProps<{
    message?: string | null;
}>();

const dismissed = ref<boolean>(false);
</script>

<template>
    <div v-if="message && !dismissed" class="relative bg-[var(--om-navy)]">
        <Container>
            <div class="flex items-center justify-center py-2 pr-8">
                <p
                    class="text-center text-xs font-medium text-white sm:text-sm"
                >
                    {{ message }}
                </p>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    class="absolute right-4 text-white/70 hover:bg-white/10 hover:text-white"
                    aria-label="Tutup"
                    @click="dismissed = true"
                >
                    <X class="size-4" aria-hidden="true" />
                </Button>
            </div>
        </Container>
    </div>
</template>
