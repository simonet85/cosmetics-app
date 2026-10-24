import { expect, test } from '@playwright/test';
import { openFilters, switchLanguage } from './helpers';

test.describe('navigation and language', () => {
    test('the storefront opens in French with one main heading', async ({ page }) => {
        await page.goto('/');

        await expect(page.locator('html')).toHaveAttribute('lang', 'fr');
        await expect(page.locator('h1')).toHaveCount(1);
        await expect(page).toHaveTitle(/Glowing Cosmetics/);
        await expect(page.locator('footer').getByRole('link', { name: 'Toute la boutique' })).toBeVisible();
        await expect(page.locator('meta[name="robots"]')).toHaveCount(0);
    });

    test('the skip link goes to the main content', async ({ page }) => {
        await page.goto('/');

        await page.keyboard.press('Tab');
        await expect(page.locator('a.skip-link')).toBeFocused();
        await page.keyboard.press('Enter');

        await expect(page).toHaveURL(/#content$/);
        await expect(page.locator('main#content')).toBeVisible();
    });

    test('the language choice sticks after a reload and can be switched back', async ({ page }) => {
        await page.goto('/');

        await switchLanguage(page, 'en');

        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await expect(page.locator('footer').getByRole('link', { name: 'The whole shop' })).toBeVisible();

        await page.reload();
        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await expect(page.locator('footer').getByRole('link', { name: 'The whole shop' })).toBeVisible();

        await switchLanguage(page, 'fr');

        await expect(page.locator('footer').getByRole('link', { name: 'Toute la boutique' })).toBeVisible();
    });

    test('legal pages say they only exist in French when the site is in English', async ({ page }) => {
        await page.goto('/terms');

        await expect(page.getByText('Ce document juridique n\'existe qu\'en français.')).toHaveCount(0);

        await switchLanguage(page, 'en');
        await expect(page.getByText('This legal document is only available in French. The French text prevails.')).toBeVisible();

        await switchLanguage(page, 'fr');
        await expect(page.getByText('Ce document juridique n\'existe qu\'en français.')).toHaveCount(0);
    });

    test('searching the shop keeps only the matching products', async ({ page }) => {
        await page.goto('/shop');

        const name = (await page.locator('article h3').first().textContent())?.trim() ?? '';

        await openFilters(page);
        await page.locator('#filters input[name="search"]').fill(name);
        await page.getByRole('button', { name: 'Appliquer' }).click();

        await expect(page).toHaveURL(/\/shop\?.*search=/);
        await expect(page.locator('article').filter({ hasText: name }).first()).toBeVisible();

        await openFilters(page);
        await page.getByRole('link', { name: 'Réinitialiser' }).click();
        await expect(page).toHaveURL(/\/shop$/);
    });

    test('a product page gives the full purchase information', async ({ page }) => {
        await page.goto('/shop');
        const firstProduct = page.locator('article h3 a').first();
        const name = (await firstProduct.textContent())?.trim() ?? '';
        await firstProduct.click();

        await expect(page).toHaveURL(/\/products\//);
        await expect(page.locator('h1')).toHaveText(name);
        await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
        await expect(page.getByText('Prix net')).toBeVisible();
        await expect(page.getByText(/En stock|Plus que \d+ en stock/)).toBeVisible();
    });
});
