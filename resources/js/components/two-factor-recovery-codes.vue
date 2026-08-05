<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Eye, EyeOff, RefreshCw } from 'lucide-vue-next';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/alert-error.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
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
    <Card class="w-full shadow-none">
        <CardHeader class="pb-0">
            <CardTitle class="text-[13px]">Kode pemulihan 2FA</CardTitle>
            <CardDescription class="leading-5">
                Simpan di tempat aman. Dipakai kalau HP autentikator hilang.
            </CardDescription>
        </CardHeader>

        <CardContent class="flex flex-col gap-3 pt-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Button
                    type="button"
                    variant="outline"
                    size="xl"
                    @click="toggleRecoveryCodesVisibility"
                >
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                    />
                    {{
                        isRecoveryCodesVisible
                            ? 'Sembunyikan kode'
                            : 'Lihat kode pemulihan'
                    }}
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        type="submit"
                        variant="outline"
                        size="xl"
                        :disabled="processing"
                    >
                        <RefreshCw />
                        Buat ulang kode
                    </Button>
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
                <div v-else class="flex flex-col gap-2">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="grid gap-1 rounded-md bg-muted p-3 font-mono text-[13px] text-foreground"
                    >
                        <div
                            v-if="!recoveryCodesList.length"
                            class="flex flex-col gap-2"
                        >
                            <Skeleton
                                v-for="n in 8"
                                :key="n"
                                class="h-4 w-full"
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
                    <p class="select-none text-sm leading-5 text-muted-foreground">
                        Tiap kode hanya bisa dipakai sekali. Butuh yang baru? Klik
                        <span class="font-semibold text-foreground"
                            >Buat ulang kode</span
                        >.
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
