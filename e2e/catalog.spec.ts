import { expect, test } from '@playwright/test';
import { expectNoCriticalA11y, gotoFirstProduct } from './helpers/qa';

test.describe('TC-UI-CAT — Guest catalog (browser)', () => {
    test('TC-UI-CAT-001 home renders brand + shop entry @smoke', async ({ page }) => {
        await page.goto('/');
        // Desktop: OceanMall wordmark. Mobile header hides wordmark — use home content + nav.
        const visibleBrand = page.getByText('OceanMall').filter({ visible: true });
        if (await visibleBrand.count()) {
            await expect(visibleBrand.first()).toBeVisible();
        } else {
            await expect(page.getByRole('link', { name: /^Beranda$/i })).toBeVisible();
            await expect(page.getByText(/Promo hari ini|Produk Unggulan/i).first()).toBeVisible();
        }
        await expect(
            page.getByRole('link', { name: /masuk|daftar|belanja/i }).or(
                page.getByRole('button', { name: /masuk|daftar/i }),
            ).filter({ visible: true }).first(),
        ).toBeVisible();
        await expectNoCriticalA11y(page, 'home');
    });

    test('TC-UI-CAT-002 shop grid + sort controls @smoke', async ({ page }) => {
        await page.goto('/shop');
        await expect(page.getByRole('main').getByRole('heading', { name: /belanja/i })).toBeVisible();
        await expect(page.locator('main a[href*="/shop/"]').first()).toBeVisible();
        await expectNoCriticalA11y(page, 'shop');
    });

    test('TC-UI-CAT-003 categories index', async ({ page }) => {
        await page.goto('/categories');
        await expect(page.getByRole('main').getByRole('heading', { name: /kategori/i })).toBeVisible();
        await expectNoCriticalA11y(page, 'categories');
    });

    test('TC-UI-CAT-004 search empty state is friendly', async ({ page }) => {
        await page.goto('/search?q=zzzznomatchqa');
        await expect(page.getByText(/tidak ada|tidak ditemukan|hasil/i).first()).toBeVisible();
        await expectNoCriticalA11y(page, 'search-empty');
    });

    test('TC-UI-CAT-005 PDP loads product detail @smoke', async ({ page }) => {
        await gotoFirstProduct(page);
        await expect(
            page.locator('main h1').filter({ hasNotText: /detail produk/i }).first(),
        ).toBeVisible();
        await expect(
            page.locator('main').getByRole('button', {
                name: /tambah ke keranjang|pilih opsi|stok habis/i,
            }).first(),
        ).toBeVisible();
        await expectNoCriticalA11y(page, 'pdp');
    });
});
