import { expect, test, type Browser, type Page } from '@playwright/test';

/**
 * The whole template setup, as the shop owner does it: sign in to the admin of the local test shop, choose the
 * Glowing Beauty template for the home page and the shop list, set an offer, then look at both pages.
 * The seeded local admin account (database/seeders/UserSeeder.php) is a test account of this development shop.
 *
 * The templates are shop-wide settings: run the two projects one after the other (`--workers=1`), never together.
 * Each project sets the templates before its tests and puts the classic ones back at the end.
 */
test.describe.configure({ mode: 'serial' });

const shot = (name: string) => `test-results/screens/${name}.png`;

async function signInAsAdmin(page: Page) {
    await page.goto('/login');
    await page.locator('#field-email').fill('admin@cosmetics.test');
    await page.locator('#field-password').fill('password');
    await page.getByRole('button', { name: 'Se connecter' }).click();
    await expect(page).not.toHaveURL(/\/login/);
}

async function chooseTemplates(page: Page, home: 'classic' | 'beauty', shop: 'classic' | 'beauty', offerEnds: string) {
    await page.goto('/admin/settings');
    await expect(page.getByRole('heading', { name: 'Apparence de la boutique' })).toBeVisible();
    await page.locator(`input[name="storefront_template_home"][value="${home}"]`).check();
    await page.locator(`input[name="storefront_template_shop"][value="${shop}"]`).check();
    await page.locator('#home_offer_ends_at').fill(offerEnds);
    await page.getByRole('button', { name: /Enregistrer les Param/ }).click();
    await expect(page.getByText('Paramètres mis à jour avec succès.').first()).toBeVisible();
}

/** The owner works at a desk: a desktop-size window, whatever project is running. */
async function ownerPage(browser: Browser, baseURL: string | undefined, name?: string) {
    const context = await browser.newContext({ baseURL, viewport: { width: 1440, height: 900 }, locale: 'fr-FR' });
    const page = await context.newPage();
    await signInAsAdmin(page);
    if (name) {
        await page.goto('/admin/settings');
        await page.locator('#appearance').scrollIntoViewIfNeeded();
        await page.screenshot({ path: shot(name) });
    }
    return { context, page };
}

test.beforeAll(async ({ browser }, testInfo) => {
    const owner = await ownerPage(browser, testInfo.project.use.baseURL, `admin-appearance-${testInfo.project.name}`);
    const end = new Date(Date.now() + 5 * 86_400_000).toISOString().slice(0, 16);
    await chooseTemplates(owner.page, 'beauty', 'beauty', end);
    await expect(owner.page.locator('input[name="storefront_template_home"][value="beauty"]')).toBeChecked();
    await expect(owner.page.locator('input[name="storefront_template_shop"][value="beauty"]')).toBeChecked();
    await owner.context.close();
});

test.afterAll(async ({ browser }, testInfo) => {
    const owner = await ownerPage(browser, testInfo.project.use.baseURL);
    await chooseTemplates(owner.page, 'classic', 'classic', '');
    await owner.context.close();
});

test('the Glowing Beauty home page: slider, rayons, shelves, before/after, countdown', async ({ page }, testInfo) => {
    await page.goto('/');
    await expect(page.locator('[data-template="beauty"]')).toBeVisible();
    await expect(page.locator('h1')).toHaveCount(1);

    // 1. hero slider: arrows move it (paused first, or its own auto-advance would move it during the test)
    expect(await page.locator('[data-slider] [data-slide]').count()).toBeGreaterThanOrEqual(2);
    await page.locator('[data-slider-toggle]').first().click();
    await expect(page.locator('[data-slider-toggle]').first()).toHaveAttribute('aria-pressed', 'true');
    const track = page.locator('[data-slider-track]');
    const start = await track.evaluate((el) => Math.round(el.scrollLeft));
    await page.locator('[data-slider-next]').first().click();
    await expect(page.locator('[data-slider-dot="1"]').first()).toHaveAttribute('aria-current', 'true');
    await page.locator('[data-slider-prev]').first().click();
    await expect.poll(() => track.evaluate((el) => Math.round(el.scrollLeft))).toBe(start);

    // 2 and 3. rayons and the shelves
    await expect(page.locator('#rayons')).toBeAttached();
    await expect(page.locator('section[aria-labelledby="best-sellers"] article').first()).toBeVisible();

    // 4. before / after: the range input moves the dividing line
    const compare = page.locator('[data-compare]');
    await compare.scrollIntoViewIfNeeded();
    await compare.locator('input[type="range"]').fill('20');
    await expect.poll(() => compare.evaluate((el) => (el as HTMLElement).style.getPropertyValue('--pos'))).toBe('20%');
    await page.screenshot({ path: shot(`home-compare-${testInfo.project.name}`) });

    // 5. countdown ticks
    const seconds = page.locator('[data-unit="seconds"]');
    await seconds.scrollIntoViewIfNeeded();
    const first = await seconds.textContent();
    await expect.poll(async () => await seconds.textContent(), { timeout: 5000 }).not.toBe(first);
    await expect(page.locator('[data-unit="days"]')).toHaveText(/0[45]/);

    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: shot(`home-beauty-${testInfo.project.name}`), fullPage: true });
});

test('the Glowing Beauty shop: filters, page size, columns, sort', async ({ page }, testInfo) => {
    await page.goto('/shop');
    await expect(page.locator('[data-template="beauty"]')).toBeVisible();
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('main article').first()).toBeVisible();

    // page size
    await page.getByRole('link', { name: /^9\s*produits par page/ }).click();
    await expect(page).toHaveURL(/per_page=9/);
    expect(await page.locator('main article').count()).toBeLessThanOrEqual(9);

    // sort: the select sends its form by itself and the page size is kept
    await page.locator('#sort').selectOption('price_asc');
    await expect(page).toHaveURL(/sort=price_asc/);
    await expect(page).toHaveURL(/per_page=9/);

    // columns (wide screens only)
    if (testInfo.project.name === 'desktop') {
        await page.getByRole('link', { name: /^4\s*colonnes/ }).click();
        await expect(page).toHaveURL(/cols=4/);
        await expect(page.locator('main ul.grid').first()).toHaveClass(/lg:grid-cols-4/);
    }

    // price filter through the sidebar (a disclosure on phones)
    if (testInfo.project.name === 'mobile') {
        await page.locator('#filters summary').click();
    }
    await page.locator('#field-max_price').fill('30');
    await page.getByRole('button', { name: 'Appliquer' }).click();
    await expect(page).toHaveURL(/max_price=30/);
    await expect(page.locator('[data-template="beauty"]')).toBeVisible();

    await page.screenshot({ path: shot(`shop-beauty-${testInfo.project.name}`), fullPage: true });
});

test('a product can be added to the cart from a Glowing Beauty tile', async ({ page }) => {
    await page.goto('/shop');
    const tile = page.locator('main article').filter({ has: page.locator('form[data-add-to-cart] button[type="submit"]') }).first();
    await tile.locator('form[data-add-to-cart] button[type="submit"]').click();
    await expect(page.locator('[data-count="cart"]').first()).toHaveText('1');
});

test('both pages speak English when the language is English', async ({ page }, testInfo) => {
    await page.goto('/langue/en');
    await page.goto('/');
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.getByText('Drag to compare')).toBeVisible();
    await expect(page.getByText('Limited-time offer')).toBeVisible();

    await page.goto('/shop');
    if (testInfo.project.name === 'mobile') {
        await page.locator('#filters summary').click();   // the filters are a disclosure on phones
    }
    await expect(page.getByText('Filter by price')).toBeVisible();
    await page.goto('/langue/fr');
});
