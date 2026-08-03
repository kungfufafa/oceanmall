<script setup lang="ts">
import { X } from 'lucide-vue-next';
import { ref } from 'vue';
import Container from './Container.vue';

defineProps<{
    message?: string | null;
}>();

const dismissed = ref<boolean>(false);
</script>

<template>
    <div
        v-if="message && !dismissed"
        class="relative bg-zinc-900 dark:bg-white"
    >
        <Container>
            <div class="flex items-center justify-center py-2">
                <p
                    class="text-center text-xs font-medium text-white sm:text-sm dark:text-zinc-900"
                >
                    {{ message }}
                </p>
                <button
                    type="button"
                    class="absolute right-4 text-white/60 hover:text-white dark:text-zinc-400 dark:hover:text-zinc-900"
                    aria-label="Dismiss"
                    @click="dismissed = true"
                >
                    <X class="size-4" aria-hidden="true" />
                </button>
            </div>
        </Container>
    </div>
</template>
