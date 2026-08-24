import AxeBuilder from '@axe-core/playwright';
import { expect  } from '@playwright/test';
import type {Page} from '@playwright/test';

export const qaUser = {
    email: process.env.PLAYWRIGHT_EMAIL
        || process.env.DEPLOY_QA_EMAIL
        || 'customer@oceanmall.test',
    password: process.env.PLAYWRIGHT_PASSWORD
        || process.env.DEPLOY_QA_PASSWORD
        || 'password123',
};

/** Serious a11y violations only (critical + serious). */
export async function expectNoCriticalA11y(page: Page, context: string): Promise<void> {
    const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
        // Color contrast is tracked as polish noise on marketing surfaces;
        // critical keyboard/name issues still fail the suite.
        .disableRules(['color-contrast'])
        .analyze();

    const serious = results.violations.filter((v) =>
        v.impact === 'critical' || v.impact === 'serious',
    );

    expect(
        serious,
        `${context}: critical/serious a11y violations:\n${serious
            .map((v) => `- [${v.impact}] ${v.id}: ${v.help} (${v.nodes.length} nodes)`)
            .join('\n')}`,
    ).toEqual([]);
}

export async function loginAsCustomer(page: Page): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('Email').fill(qaUser.email);
    await page.locator('#password').fill(qaUser.password);
    await page.getByRole('button', { name: /masuk/i }).click();
    await expect(page).not.toHaveURL(/\/login$/, { timeout: 20_000 });
}

export async function gotoFirstProduct(page: Page): Promise<void> {
    await page.goto('/shop');
    await expect(page.getByRole('main').getByRole('heading', { name: /belanja/i })).toBeVisible();
    const productLink = page.locator('main a[href*="/shop/"]').first();
    await expect(productLink).toBeVisible({ timeout: 15_000 });
    await productLink.click();
    await expect(page).toHaveURL(/\/shop\/[^/?]+/);
}

export async function selectFirstAvailableVariantIfNeeded(page: Page): Promise<void> {
    const cta = page.locator('main').getByRole('button', {
        name: /tambah ke keranjang|pilih opsi|stok habis/i,
    }).first();
    await expect(cta).toBeVisible({ timeout: 15_000 });
    const label = ((await cta.textContent()) ?? '').toLowerCase();

    if (!label.includes('pilih opsi')) {
        return;
    }

    // One enabled value per attribute group (Label + ToggleGroup).
    // Do not click gallery thumbnails or review-sort toggles.
    const groups = page
        .locator('main div.flex.flex-col.gap-2')
        .filter({ has: page.locator('label') })
        .filter({ has: page.locator('[data-slot="toggle-group"]') });

    const count = await groups.count();

    for (let i = 0; i < count; i++) {
        const option = groups
            .nth(i)
            .locator('[data-slot="toggle-group-item"]:not([disabled])')
            .first();

        if (await option.count()) {
            await option.click();
        }
    }

    await expect(cta).toHaveText(/tambah ke keranjang|stok habis|menambahkan/i, {
        timeout: 10_000,
    });
}
