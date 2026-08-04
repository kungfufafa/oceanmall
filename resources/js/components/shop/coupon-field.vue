<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/input-error.vue';
import * as cartCoupon from '@/routes/shop/cart/coupon';

const props = defineProps<{
    couponCode?: string | null;
}>();

const page = usePage();

const form = useForm({
    code: '',
});

const appliedCode = computed(() => props.couponCode ?? null);
const codeError = computed(
    () =>
        form.errors.code ||
        (page.props.errors as Record<string, string> | undefined)?.code,
);

function applyCoupon(): void {
    form
        .transform((data) => ({
            code: data.code.trim().toUpperCase(),
        }))
        .post(cartCoupon.store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
}

function removeCoupon(): void {
    router.delete(cartCoupon.destroy.url(), { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-2">
        <label for="coupon-code" class="om-label">Kode kupon</label>

        <div
            v-if="appliedCode"
            class="flex items-center justify-between gap-3 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2"
        >
            <div class="min-w-0">
                <p class="text-[13px] font-semibold text-emerald-800">
                    {{ appliedCode }}
                </p>
                <p class="text-[12px] text-emerald-700">Kupon diterapkan</p>
            </div>
            <button
                type="button"
                class="om-action-muted shrink-0 !text-[12px]"
                @click="removeCoupon"
            >
                Hapus
            </button>
        </div>

        <template v-else>
            <form class="flex items-center gap-2" @submit.prevent="applyCoupon">
                <input
                    id="coupon-code"
                    v-model="form.code"
                    type="text"
                    name="code"
                    autocomplete="off"
                    placeholder="Masukkan kode"
                    class="om-control min-w-0 flex-1 border border-zinc-200 bg-white px-3 text-zinc-900 outline-none placeholder:text-zinc-400 focus:border-[var(--om-navy)]"
                    :class="codeError && 'border-red-400 focus:border-red-500'"
                />
                <button
                    type="submit"
                    class="om-btn-outline inline-flex shrink-0 items-center justify-center px-4 disabled:opacity-50"
                    :disabled="form.processing || !form.code.trim()"
                >
                    Terapkan
                </button>
            </form>
            <InputError :message="codeError" />
        </template>
    </div>
</template>
