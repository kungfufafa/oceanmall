<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { Check, Copy } from 'lucide-vue-next';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/alert-error.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import InputError from '@/components/input-error.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: '2FA aktif',
            description:
                'Autentikasi dua faktor sudah aktif. Simpan kode pemulihanmu di tempat aman.',
            buttonText: 'Tutup',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Verifikasi kode',
            description: 'Masukkan kode 6 digit dari aplikasi autentikator.',
            buttonText: 'Konfirmasi',
        };
    }

    return {
        title: 'Aktifkan 2FA',
        description:
            'Scan QR code atau masukkan kunci setup di aplikasi autentikator.',
        buttonText: 'Lanjut',
    };
});

const canConfirmCode = computed(() => code.value.length === 6);

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            pinInputContainerRef.value?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (open) => {
        if (!open) {
            resetModalState();

            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader class="space-y-1.5 text-left">
                <DialogTitle class="text-[15px] font-semibold text-zinc-900">
                    {{ modalConfig.title }}
                </DialogTitle>
                <DialogDescription class="om-meta text-left leading-5">
                    {{ modalConfig.description }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4">
                <template v-if="!showVerificationStep">
                    <AlertError v-if="errors?.length" :errors="errors" />
                    <template v-else>
                        <div
                            class="relative mx-auto aspect-square w-56 overflow-hidden rounded-md border border-zinc-200 bg-white"
                        >
                            <div
                                v-if="!qrCodeSvg"
                                class="absolute inset-0 z-10 flex items-center justify-center"
                            >
                                <Spinner class="size-6" />
                            </div>
                            <div
                                v-else
                                class="flex size-full items-center justify-center p-4"
                                v-html="qrCodeSvg"
                            />
                        </div>

                        <Button
                            type="button"
                            size="xl"
                            class="w-full disabled:cursor-not-allowed"
                            :disabled="!qrCodeSvg"
                            @click="handleModalNextStep"
                        >
                            Lanjut
                        </Button>

                        <div class="relative flex items-center justify-center">
                            <div
                                class="absolute inset-x-0 top-1/2 h-px bg-zinc-200"
                            />
                            <span
                                class="relative bg-white px-2 text-[12px] text-zinc-500"
                            >
                                atau masukkan kode manual
                            </span>
                        </div>

                        <div
                            class="flex overflow-hidden rounded-md border border-zinc-200"
                        >
                            <div
                                v-if="!manualSetupKey"
                                class="flex h-11 w-full items-center justify-center bg-zinc-50"
                            >
                                <Spinner class="size-4" />
                            </div>
                            <template v-else>
                                <input
                                    type="text"
                                    readonly
                                    :value="manualSetupKey"
                                    class="om-control h-11 min-w-0 flex-1 border-0 bg-white px-3 font-mono text-[13px] text-zinc-900 outline-none"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-11 shrink-0 items-center border-l border-zinc-200 px-3 text-zinc-600 hover:bg-zinc-50"
                                    @click="copy(manualSetupKey || '')"
                                >
                                    <Check
                                        v-if="copied"
                                        class="size-4 text-green-600"
                                    />
                                    <Copy v-else class="size-4" />
                                </button>
                            </template>
                        </div>
                    </template>
                </template>

                <template v-else>
                    <Form
                        v-bind="confirm.form()"
                        error-bag="confirmTwoFactorAuthentication"
                        reset-on-error
                        class="flex flex-col gap-3.5"
                        @finish="code = ''"
                        @success="isOpen = false"
                        v-slot="{ errors: formErrors, processing }"
                    >
                        <input type="hidden" name="code" :value="code" />

                        <div
                            ref="pinInputContainerRef"
                            class="flex flex-col items-center gap-3 py-1"
                        >
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="processing"
                                autofocus
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError :message="formErrors?.code" />
                        </div>

                        <div class="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="xl"
                                class="flex-1"
                                :disabled="processing"
                                @click="showVerificationStep = false"
                            >
                                Kembali
                            </Button>
                            <AuthSubmitButton
                                class="!w-auto flex-1"
                                label="Konfirmasi"
                                :enabled="canConfirmCode"
                                :processing="processing"
                            />
                        </div>
                    </Form>
                </template>
            </div>
        </DialogContent>
    </Dialog>
</template>
