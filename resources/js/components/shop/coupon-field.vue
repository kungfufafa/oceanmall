<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/input-error.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    <div class="flex flex-col gap-2">
        <Label for="coupon-code">Kode kupon</Label>

        <Alert v-if="appliedCode" variant="success" class="py-2">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <AlertTitle class="text-[13px]">{{ appliedCode }}</AlertTitle>
                    <AlertDescription class="text-[12px]">
                        Kupon diterapkan
                    </AlertDescription>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="shrink-0 text-xs"
                    @click="removeCoupon"
                >
                    Hapus
                </Button>
            </div>
        </Alert>

        <template v-else>
            <form class="flex items-center gap-2" @submit.prevent="applyCoupon">
                <Input
                    id="coupon-code"
                    v-model="form.code"
                    type="text"
                    name="code"
                    autocomplete="off"
                    placeholder="Masukkan kode"
                    class="h-[var(--om-control-height)] min-w-0 flex-1 text-[13px]"
                    :aria-invalid="Boolean(codeError) || undefined"
                />
                <Button
                    type="submit"
                    variant="outline"
                    size="xl"
                    class="shrink-0"
                    :disabled="form.processing || !form.code.trim()"
                >
                    Terapkan
                </Button>
            </form>
            <InputError :message="codeError" />
        </template>
    </div>
</template>
