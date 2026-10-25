import { expect, test } from '@playwright/test';

/** The information tabs of a product page (demo data of the local shop: database/seeders/DemoProductDetailsSeeder.php). */
const product = '/products/nourishing-hair-oil';

test.describe('product information tabs', () => {
    test('the first tab is shown, the others open on click', async ({ page }) => {
        await page.goto(product);

        const tabs = page.locator('[data-tabs] [role="tab"]');
        await expect(tabs).toHaveCount(4);
        await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
        await expect(page.locator('#info-panel-usage')).toBeVisible();
        await expect(page.locator('#info-panel-precautions')).toBeHidden();

        await page.locator('#info-tab-precautions').click();

        await expect(page.locator('#info-tab-precautions')).toHaveAttribute('aria-selected', 'true');
        await expect(page.locator('#info-panel-precautions')).toBeVisible();
        await expect(page.locator('#info-panel-usage')).toBeHidden();
        await expect(page.locator('#info-panel-precautions')).toContainText('Pour usage externe');
    });

    test('the arrow keys, Home and End move between the tabs', async ({ page }) => {
        await page.goto(product);

        await page.locator('#info-tab-usage').focus();
        await page.keyboard.press('ArrowRight');
        await expect(page.locator('#info-tab-ingredients')).toBeFocused();
        await expect(page.locator('#info-tab-ingredients')).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.press('End');
        await expect(page.locator('#info-tab-storage')).toHaveAttribute('aria-selected', 'true');
        await expect(page.locator('#info-panel-storage')).toContainText('hors de portée des enfants');

        await page.keyboard.press('ArrowRight');
        await expect(page.locator('#info-tab-usage')).toHaveAttribute('aria-selected', 'true');   // wraps around

        await page.keyboard.press('Home');
        await expect(page.locator('#info-tab-usage')).toBeFocused();
    });

    test('the key ingredients open and close, with their benefits', async ({ page }) => {
        await page.goto(product);
        await page.locator('#info-tab-ingredients').click();

        const panel = page.locator('#info-panel-ingredients');
        await expect(panel).toContainText('ARGANIA SPINOSA');

        const first = panel.locator('details').first();
        await expect(first).not.toHaveAttribute('open', '');
        await first.locator('summary').click();
        await expect(first).toHaveAttribute('open', '');
        await expect(first).toContainText('Nourrissante');
        await first.locator('summary').click();
        await expect(first).not.toHaveAttribute('open', '');
    });

    test('the tab bar fits the screen: it scrolls inside itself, the page never scrolls sideways', async ({ page }) => {
        await page.goto(product);

        expect(await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth)).toBe(false);
        for (const tab of await page.locator('[data-tabs] [role="tab"]').all()) {
            expect((await tab.boundingBox())?.height ?? 0).toBeGreaterThanOrEqual(44);   // touch target
        }
    });

    test('without scripts every section is shown one after the other', async ({ browser, baseURL }) => {
        const context = await browser.newContext({ baseURL, javaScriptEnabled: false });
        const page = await context.newPage();
        await page.goto(product);

        await expect(page.locator('[data-tabs] [role="tabpanel"]:visible')).toHaveCount(4);
        await expect(page.locator('[data-tabs] [role="tablist"]')).toBeHidden();
        await context.close();
    });
});
