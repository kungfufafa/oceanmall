import { expect, test } from '@playwright/test';
import {
    expectNoCriticalA11y,
    gotoFirstProduct,
    loginAsCustomer,
    selectFirstAvailableVariantIfNeeded,
} from './helpers/qa';

test.describe('TC-UI-CART — Cart & checkout (browser)', () => {
    test('TC-UI-CART-001 empty cart empty-state @smoke', async ({ page }) => {
        await page.goto('/cart');
        await expect(page.getByRole('main').getByRole('heading', { name: /keranjang/i })).toBeVisible();
        await expect(page.getByText(/keranjang masih kosong/i)).toBeVisible();
        await expect(page.getByRole('link', { name: /belanja sekarang/i })).toBeVisible();
        await expectNoCriticalA11y(page, 'cart-empty');
    });

    test('TC-UI-CART-002 add to cart from PDP @smoke', async ({ page }) => {
        await gotoFirstProduct(page);
        await selectFirstAvailableVariantIfNeeded(page);

        const addBtn = page.locator('main').getByRole('button', {
            name: /tambah ke keranjang/i,
        }).first();
        await expect(addBtn).toBeEnabled({ timeout: 15_000 });
        await Promise.all([
            page.waitForLoadState('networkidle').catch(() => undefined),
            addBtn.click(),
        ]);
        await page.waitForTimeout(400);

        await page.goto('/cart');
        await expect(page.getByText(/keranjang masih kosong/i)).toHaveCount(0);
        await expect(
            page.getByRole('link', { name: /bayar|checkout|lanjut|masuk untuk/i }).or(
                page.getByRole('button', { name: /bayar|checkout|lanjut|masuk untuk/i }),
            ).first(),
        ).toBeVisible({ timeout: 15_000 });
        await expectNoCriticalA11y(page, 'cart-with-items');
    });

    test('TC-UI-CHK-001 checkout address + rates happy path @smoke', async ({ page }) => {
        test.setTimeout(120_000);
        await loginAsCustomer(page);

        await gotoFirstProduct(page);
        await selectFirstAvailableVariantIfNeeded(page);
        const addBtn = page.locator('main').getByRole('button', {
            name: /tambah ke keranjang/i,
        }).first();
        await expect(addBtn).toBeEnabled({ timeout: 15_000 });
        await addBtn.click();

        await page.goto('/checkout');
        await expect(page).toHaveURL(/\/checkout/);
        await expect(
            page.getByRole('heading', {
                name: /metode pengiriman|ringkasan pesanan|alamat pengiriman|lengkapi alamat/i,
            }).first(),
        ).toBeVisible();

        // Select a shipping rate when already past address (saved address session).
        const rate = page.locator('main').getByRole('radio').first();

        if (await rate.isVisible().catch(() => false)) {
            await rate.click();
            await expect(
                page.getByText(/pengiriman|kurir|ongkir|pembayaran|qris|transfer|lanjut/i).first(),
            ).toBeVisible({ timeout: 15_000 });
            await expectNoCriticalA11y(page, 'checkout');

            return;
        }

        const saved = page.locator('main').getByText(/jakarta|jalan|alamat/i).first();

        if (await saved.isVisible().catch(() => false)) {
            await saved.click().catch(() => undefined);
        }

        const dest = page.getByPlaceholder(/cari|kecamatan|destinasi|jakarta/i).first();

        if (await dest.isVisible().catch(() => false)) {
            await dest.fill('Jakarta Selatan');
            const suggestion = page.locator('main').getByRole('button').filter({
                hasText: /jakarta/i,
            }).first();
            await expect(suggestion).toBeVisible({ timeout: 20_000 });
            await suggestion.click();
        }

        const street = page.getByLabel(/alamat|jalan|street/i).first();

        if (await street.isVisible().catch(() => false)) {
            const val = await street.inputValue().catch(() => '');

            if (!val) {
await street.fill('Jl. Melawai Raya No. 1');
}
        }

        const continueBtn = page.locator('main').getByRole('button', {
            name: /lanjut|simpan|pakai alamat|kirim/i,
        }).first();

        if (await continueBtn.isVisible().catch(() => false)) {
            await continueBtn.click();
        }

        await expect(
            page.getByText(/pengiriman|kurir|ongkir|pembayaran|qris|transfer|metode pengiriman/i).first(),
        ).toBeVisible({ timeout: 30_000 });

        const shippingRate = page.locator('main').getByRole('radio').first();

        if (await shippingRate.isVisible().catch(() => false)) {
            await shippingRate.click();
        }

        await expectNoCriticalA11y(page, 'checkout');
    });

    test('TC-UI-ACCT-001 account orders page', async ({ page }) => {
        await loginAsCustomer(page);
        await page.goto('/account/orders');
        await expect(page).toHaveURL(/\/account\/orders/);
        await expect(
            page.getByText(/Belum ada pesanan/i).or(page.getByText(/#ORD-/i)).or(
                page.getByRole('link', { name: /^Detail$/i }),
            ).first(),
        ).toBeVisible();
        await expectNoCriticalA11y(page, 'account-orders');
    });
});
