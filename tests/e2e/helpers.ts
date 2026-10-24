import { expect, type Page } from '@playwright/test';

export type Locale = 'fr' | 'en';

/**
 * Add the first product of the shop list to the cart and wait for the header counter.
 * Returns the product name as it is displayed, so a test can find it again in the cart.
 */
export async function addFirstProductToCart(page: Page): Promise<string> {
    await page.goto('/shop');

    const card = page
        .locator('article')
        .filter({ has: page.locator('form[data-add-to-cart] button[type="submit"]') })
        .first();

    const name = (await card.locator('h3').textContent())?.trim() ?? '';
    await card.locator('form[data-add-to-cart] button[type="submit"]').click();
    await expect(page.locator('[data-count="cart"]').first()).toHaveText('1');

    return name;
}

/** Open the shop filter disclosure on phones; it is already open on desktop. */
export async function openFilters(page: Page): Promise<void> {
    const filters = page.locator('#filters');
    const open = await filters.evaluate((element) => (element as HTMLDetailsElement).open);
    if (!open) {
        await filters.locator('summary').click();
    }
}

/** Switch language with the footer switcher, which is visible at every screen width. */
export async function switchLanguage(page: Page, locale: Locale): Promise<void> {
    await page.locator(`footer a[hreflang="${locale}"]`).click();
    await expect(page.locator('html')).toHaveAttribute('lang', locale);
}
