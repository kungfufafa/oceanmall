import { expect, test } from '@playwright/test';

const admin = {
    email: process.env.PLAYWRIGHT_ADMIN_EMAIL || 'admin@oceanmall.test',
    password: process.env.PLAYWRIGHT_ADMIN_PASSWORD || 'password123',
};

async function loginAsAdmin(page: import('@playwright/test').Page): Promise<void> {
    await page.goto('/cpanel/login');
    await expect(page.getByRole('textbox', { name: /email/i })).toBeVisible({ timeout: 20_000 });
    await page.getByRole('textbox', { name: /email/i }).fill(admin.email);
    await page.locator('#form\\.password').fill(admin.password);
    await page.locator('#form\\.password').press('Enter');
    await expect(page).not.toHaveURL(/\/cpanel\/login/, { timeout: 30_000 });
}

test.describe('TC-UI-CPANEL — Shopper shipping (browser)', () => {
    test('TC-UI-CPANEL-001 admin login reaches dashboard @smoke', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/cpanel/dashboard');
        await expect(page).toHaveURL(/\/cpanel\/dashboard/);
        await expect(page.getByText(/dashboard|ringkasan|penjualan|orders/i).first()).toBeVisible({
            timeout: 20_000,
        });
    });

    test('TC-UI-CPANEL-002 shipments page does not show manual tracking CRUD', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/cpanel/orders/shipments');
        await expect(page).toHaveURL(/\/cpanel\/orders\/shipments/);
        await expect(page.getByText(/pengiriman|shipments/i).first()).toBeVisible({ timeout: 20_000 });
        await expect(page.getByPlaceholder('e.g. 1Z999AA10123456784')).toHaveCount(0);
        await expect(page.getByPlaceholder('https://your-tracking-url.com')).toHaveCount(0);
    });

    test('TC-UI-CPANEL-003 order detail shows RajaOngkir fulfillment, not typed labels', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/cpanel/orders');
        await expect(page).toHaveURL(/\/cpanel\/orders/);

        const firstOrder = page.locator('a[href*="/cpanel/orders/"][href*="/detail"]').first();

        if ((await firstOrder.count()) === 0) {
            test.info().annotations.push({
                type: 'note',
                description: 'No orders in local DB; skipped order-detail fulfillment check.',
            });

            return;
        }

        await firstOrder.click();
        await expect(page).toHaveURL(/\/cpanel\/orders\/.+\/detail/);
        await expect(
            page.getByText(/RajaOngkir|Komerce|Terbitkan Resi|Cetak Stiker|Menunggu pelunasan/i).first(),
        ).toBeVisible({ timeout: 20_000 });
        await expect(page.getByPlaceholder('e.g. 1Z999AA10123456784')).toHaveCount(0);
    });
});
