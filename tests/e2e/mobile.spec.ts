import { expect, test } from '@playwright/test';
import { addFirstProductToCart } from './helpers';

test.describe('phone layout', () => {
    test('the drawer opens and leads to a category', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'mobile', 'The drawer only exists on small screens');

        await page.goto('/');
        await page.getByRole('button', { name: 'Ouvrir le menu' }).click();

        const drawer = page.locator('#nav-drawer');
        await expect(drawer).toBeVisible();

        const category = drawer.locator('a[href*="/shop/category/"]').first();
        const label = (await category.textContent())?.trim() ?? '';
        await category.click();

        await expect(page).toHaveURL(/\/shop\/category\//);
        await expect(page.locator('h1')).toHaveText(label);
    });

    test('the header cart opens the cart on a phone', async ({ page }, testInfo) => {
        test.skip(testInfo.project.name !== 'mobile', 'Touch target check only makes sense on a phone');

        const name = await addFirstProductToCart(page);
        await page.getByRole('link', { name: 'Mon panier' }).click();

        await expect(page).toHaveURL(/\/cart$/);
        await expect(page.locator('[data-cart-line]')).toContainText(name);
    });

    test('no page scrolls sideways', async ({ page }) => {
        for (const path of ['/', '/shop', '/cart', '/pages/faq']) {
            await page.goto(path);
            await page.evaluate(() => document.fonts.ready);

            const overflow = await page.evaluate(
                () => document.documentElement.scrollWidth - document.documentElement.clientWidth,
            );

            expect(overflow, `${path} is ${overflow}px wider than the viewport`).toBeLessThanOrEqual(1);
        }
    });
});
