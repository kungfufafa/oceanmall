<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, RefreshCw } from 'lucide-vue-next';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/alert-error.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <div class="w-full space-y-3 rounded-md border border-zinc-200 bg-white p-4">
        <div>
            <h3 class="text-[13px] font-semibold text-zinc-900">
                Kode pemulihan 2FA
            </h3>
            <p class="om-meta mt-1 leading-5">
                Simpan di tempat aman. Dipakai kalau HP autentikator hilang.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <button
                type="button"
                class="om-btn-outline inline-flex items-center justify-center gap-2 px-4"
                @click="toggleRecoveryCodesVisibility"
            >
                <component
                    :is="isRecoveryCodesVisible ? EyeOff : Eye"
                    class="size-4"
                />
                {{
                    isRecoveryCodesVisible
                        ? 'Sembunyikan kode'
                        : 'Lihat kode pemulihan'
                }}
            </button>

            <Form
                v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                v-bind="regenerateRecoveryCodes.form()"
                method="post"
                :options="{ preserveScroll: true }"
                @success="fetchRecoveryCodes"
                #default="{ processing }"
            >
                <button
                    type="submit"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-md border border-zinc-200 bg-white px-4 text-[13px] font-semibold text-zinc-700 disabled:opacity-50"
                    :disabled="processing"
                >
                    <RefreshCw class="size-4" />
                    Buat ulang kode
                </button>
            </Form>
        </div>

        <div
            :class="[
                'overflow-hidden transition-all duration-300',
                isRecoveryCodesVisible
                    ? 'h-auto opacity-100'
                    : 'h-0 opacity-0',
            ]"
        >
            <div v-if="errors?.length" class="mt-1">
                <AlertError :errors="errors" />
            </div>
            <div v-else class="space-y-2">
                <div
                    ref="recoveryCodeSectionRef"
                    class="grid gap-1 rounded-md bg-zinc-50 p-3 font-mono text-[13px] text-zinc-800"
                >
                    <div v-if="!recoveryCodesList.length" class="space-y-2">
                        <div
                            v-for="n in 8"
                            :key="n"
                            class="h-4 animate-pulse rounded bg-zinc-200"
                        />
                    </div>
                    <div
                        v-else
                        v-for="(code, index) in recoveryCodesList"
                        :key="index"
                    >
                        {{ code }}
                    </div>
                </div>
                <p class="om-meta select-none leading-5">
                    Tiap kode hanya bisa dipakai sekali. Butuh yang baru? Klik
                    <span class="font-semibold text-zinc-700"
                        >Buat ulang kode</span
                    >.
                </p>
            </div>
        </div>
    </div>
</template>
