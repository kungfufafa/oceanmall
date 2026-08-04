<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthPasswordField from '@/components/auth/auth-password-field.vue';
import AuthSubmitButton from '@/components/auth/auth-submit-button.vue';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Konfirmasi Password',
        description: 'Area aman. Masukkan passwordmu untuk melanjutkan.',
    },
});

const password = ref('');
const canSubmit = computed(() => password.value.length > 0);
</script>

<template>
    <Head title="Konfirmasi Password" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="flex flex-col gap-3.5"
    >
        <AuthPasswordField
            id="password"
            v-model="password"
            label="Password"
            name="password"
            required
            autocomplete="current-password"
            autofocus
            placeholder="Masukkan passwordmu *"
            :error="errors.password"
        />

        <AuthSubmitButton
            class="mt-2"
            label="Konfirmasi"
            :enabled="canSubmit"
            :processing="processing"
            data-test="confirm-password-button"
        />
    </Form>
</template>
