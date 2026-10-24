import { expect, test } from '@playwright/test';
import { addFirstProductToCart } from './helpers';

test.describe('cart, wishlist and product page', () => {
    test('adding a product from the shop updates the counter and the cart page', async ({ page }) => {
        const name = await addFirstProductToCart(page);

        await expect(page.locator('[data-count="cart"]').first()).toHaveText('1');

        await page.getByRole('link', { name: 'Mon panier' }).click();

        await expect(page).toHaveURL(/\/cart$/);
        await expect(page.locator('[data-cart-line]')).toHaveCount(1);
        await expect(page.locator('[data-cart-line]').first()).toContainText(name);
        await expect(page.locator('[data-cart-qty]').first()).toHaveValue('1');
    });

    test('the cart changes quantity without reloading, then empties', async ({ page }) => {
        await addFirstProductToCart(page);
        await page.goto('/cart');

        const line = page.locator('[data-cart-line]').first();

        await line.locator('[data-cart-step="1"]').click();

        await expect(line.locator('[data-cart-qty]')).toHaveValue('2');
        await expect(page.locator('[data-count="cart"]').first()).toHaveText('2');

        await line.locator('[data-cart-remove]').click();

        await expect(page.getByRole('heading', { name: 'Votre panier est vide' })).toBeVisible();
        await expect(page.locator('[data-cart-line]')).toHaveCount(0);
    });

    test('the product page adds the chosen quantity and toggles the wishlist', async ({ page }) => {
        await page.goto('/shop');
        const firstProduct = page.locator('article h3 a').first();
        await firstProduct.click();

        const wishlist = page.locator('[data-wishlist-toggle]');
        await expect(wishlist).toHaveAttribute('aria-pressed', 'false');
        await wishlist.click();
        await expect(wishlist).toHaveAttribute('aria-pressed', 'true');
        await expect(page.locator('[data-count="wishlist"]').first()).toHaveText('1');

        await page.locator('[data-product-form] input[name="quantity"]').fill('2');
        await page.getByRole('button', { name: 'Ajouter au panier' }).click();

        await expect(page.locator('[data-count="cart"]').first()).toHaveText('2');

        await page.goto('/cart');
        await expect(page.locator('[data-cart-qty]').first()).toHaveValue('2');
    });
});
