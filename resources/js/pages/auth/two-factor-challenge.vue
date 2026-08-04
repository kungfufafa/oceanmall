<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import InputError from '@/components/input-error.vue';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Kode pemulihan',
            description:
                'Masukkan salah satu kode pemulihan darurat akunmu.',
            buttonText: 'pakai kode autentikator',
        };
    }

    return {
        title: 'Kode autentikasi',
        description: 'Masukkan kode dari aplikasi autentikator.',
        buttonText: 'pakai kode pemulihan',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: showRecoveryInput.value ? 'Kode pemulihan' : 'Kode autentikasi',
        description: authConfigContent.value.description,
    });
});

const showRecoveryInput = ref(false);
const code = ref('');
const recoveryCode = ref('');

const canSubmitCode = computed(() => code.value.length === 6);
const canSubmitRecovery = computed(() => recoveryCode.value.trim().length > 0);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
    recoveryCode.value = '';
};
</script>

<template>
    <Head title="Autentikasi dua faktor" />

    <template v-if="!showRecoveryInput">
        <Form
            v-bind="store.form()"
            class="flex flex-col gap-3.5"
            reset-on-error
            @error="code = ''"
            #default="{ errors, processing, clearErrors }"
        >
            <input type="hidden" name="code" :value="code" />

            <div class="flex flex-col items-center gap-3">
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
                <InputError :message="errors.code" />
            </div>

            <AuthSubmitButton
                label="Lanjut"
                :enabled="canSubmitCode"
                :processing="processing"
            />

            <p class="text-center text-[13px] text-zinc-500">
                atau kamu bisa
                <button
                    type="button"
                    class="font-bold text-[var(--om-navy)]"
                    @click="() => toggleRecoveryMode(clearErrors)"
                >
                    {{ authConfigContent.buttonText }}
                </button>
            </p>
        </Form>
    </template>

    <template v-else>
        <Form
            v-bind="store.form()"
            class="flex flex-col gap-3.5"
            reset-on-error
            #default="{ errors, processing, clearErrors }"
        >
            <AuthTextField
                id="recovery_code"
                v-model="recoveryCode"
                label="Kode pemulihan"
                type="text"
                name="recovery_code"
                autocomplete="one-time-code"
                autofocus
                placeholder="Masukkan kode pemulihan *"
                :error="errors.recovery_code"
            />

            <AuthSubmitButton
                label="Lanjut"
                :enabled="canSubmitRecovery"
                :processing="processing"
            />

            <p class="text-center text-[13px] text-zinc-500">
                atau kamu bisa
                <button
                    type="button"
                    class="font-bold text-[var(--om-navy)]"
                    @click="() => toggleRecoveryMode(clearErrors)"
                >
                    {{ authConfigContent.buttonText }}
                </button>
            </p>
        </Form>
    </template>
</template>
