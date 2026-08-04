<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import AuthTextField from '@/components/auth/auth-text-field.vue';
import { login } from '@/routes';
import { email as emailRoute } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa Password',
        description:
            'Masukkan email terdaftar. Kami kirim link untuk atur ulang password.',
        actionLabel: 'Masuk',
        actionHref: login.url(),
    },
});

defineProps<{
    status?: string;
}>();

const email = ref('');
const canSubmit = computed(() => email.value.trim().length > 0);
</script>

<template>
    <Head title="Lupa Password" />

    <div
        v-if="status"
        class="mb-5 rounded-lg bg-emerald-50 px-3.5 py-3 text-[13px] font-medium text-emerald-700"
    >
        {{ status }}
    </div>

    <Form
        v-bind="emailRoute.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <AuthTextField
            id="email"
            v-model="email"
            label="Email"
            type="email"
            name="email"
            autocomplete="email"
            autofocus
            placeholder="Masukkan emailmu *"
            :error="errors.email"
        />

        <AuthSubmitButton
            class="mt-1"
            label="Kirim link reset"
            :enabled="canSubmit"
            :processing="processing"
            data-test="email-password-reset-link-button"
        />
    </Form>
</template>
