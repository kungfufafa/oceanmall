<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes.vue';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    passwordRules: string;
};

const props = withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Keamanan',
                href: edit(),
            },
        ],
    },
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');

const canSubmitPassword = computed(
    () =>
        currentPassword.value.length > 0 &&
        password.value.length > 0 &&
        passwordConfirmation.value.length > 0,
);

function resetPasswordFields(): void {
    currentPassword.value = '';
    password.value = '';
    passwordConfirmation.value = '';
}

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <Head title="Keamanan" />

    <div class="flex flex-col gap-5">
        <div>
            <h2 class="text-[13px] font-semibold text-foreground">
                Ubah password
            </h2>
            <p class="om-meta mt-1">
                Pakai password yang kuat dan unik untuk akunmu.
            </p>
        </div>

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="flex flex-col gap-3.5"
            v-slot="{ errors, processing }"
            @success="resetPasswordFields"
        >
            <AuthPasswordField
                id="current_password"
                v-model="currentPassword"
                label="Password saat ini"
                name="current_password"
                autocomplete="current-password"
                placeholder="Password saat ini *"
                :error="errors.current_password"
            />

            <AuthPasswordField
                id="password"
                v-model="password"
                label="Password baru"
                name="password"
                autocomplete="new-password"
                placeholder="Password baru *"
                :passwordrules="props.passwordRules"
                :error="errors.password"
            />

            <AuthPasswordField
                id="password_confirmation"
                v-model="passwordConfirmation"
                label="Konfirmasi password"
                name="password_confirmation"
                autocomplete="new-password"
                placeholder="Ulangi password baru *"
                :passwordrules="props.passwordRules"
                :error="errors.password_confirmation"
            />

            <AuthSubmitButton
                class="mt-1"
                label="Simpan password"
                :enabled="canSubmitPassword"
                :processing="processing"
                data-test="update-password-button"
            />
        </Form>
    </div>

    <div
        v-if="canManageTwoFactor"
        class="mt-10 flex flex-col gap-4"
    >
        <Separator />

        <div>
            <h2 class="text-[13px] font-semibold text-foreground">
                Autentikasi dua faktor
            </h2>
            <p class="om-meta mt-1">
                Tambah lapisan keamanan saat masuk ke akun.
            </p>
        </div>

        <div v-if="!twoFactorEnabled" class="flex flex-col items-start gap-3">
            <p class="om-meta leading-5">
                Setelah diaktifkan, kamu akan diminta kode dari aplikasi
                autentikator di HP saat login.
            </p>

            <div class="w-full max-w-sm">
                <Button
                    v-if="hasSetupData"
                    type="button"
                    size="xl"
                    class="w-full"
                    @click="showSetupModal = true"
                >
                    Lanjutkan setup
                </Button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <AuthSubmitButton
                        label="Aktifkan 2FA"
                        :enabled="!processing"
                        :processing="processing"
                    />
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start gap-3">
            <p class="om-meta leading-5">
                2FA aktif. Saat login, masukkan kode dari aplikasi autentikator.
            </p>

            <Form v-bind="disable.form()" #default="{ processing }">
                <Button
                    type="submit"
                    variant="destructive"
                    class="h-10 px-4 text-[13px] font-semibold"
                    :disabled="processing"
                >
                    Nonaktifkan 2FA
                </Button>
            </Form>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
