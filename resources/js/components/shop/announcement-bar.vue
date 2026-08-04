<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { ref } from 'vue';
import Container from './container.vue';

defineProps<{
    message?: string | null;
}>();

const dismissed = ref<boolean>(false);
</script>

<template>
    <div
        v-if="message && !dismissed"
        class="relative bg-[var(--om-navy)]"
    >
        <Container>
            <div class="flex items-center justify-center py-2 pr-8">
                <p
                    class="text-center text-xs font-medium text-white sm:text-sm"
                >
                    {{ message }}
                </p>
                <button
                    type="button"
                    class="absolute right-4 text-white/70 hover:text-white"
                    aria-label="Tutup"
                    @click="dismissed = true"
                >
                    <X class="size-4" aria-hidden="true" />
                </button>
            </div>
        </Container>
    </div>
</template>
