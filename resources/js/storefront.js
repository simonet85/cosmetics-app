/*
 * Storefront behaviour, plain JavaScript (no jQuery, no Alpine).
 * Everything here is progressive: the pages work without it, it only removes round trips and adds feedback.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Toasts: announced politely, never steal focus ---------- */
export function toast(message, { type = 'info', duration = 4500 } = {}) {
    const region = document.getElementById('toast-region');
    if (!region || !message) return;

    const item = document.createElement('div');
    item.className = 'toast pointer-events-auto flex items-start gap-3 border border-ink bg-ink px-4 py-3 text-sm text-white shadow-[0_12px_32px_-12px_rgb(19_32_26/0.5)]';
    if (type === 'error') item.classList.replace('bg-ink', 'bg-danger');
    item.textContent = message;
    region.appendChild(item);

    setTimeout(() => item.remove(), duration);
}

/* ---------- Header counters ---------- */
function setCount(name, value) {
    document.querySelectorAll(`[data-count="${name}"]`).forEach((el) => {
        el.textContent = value;
        el.hidden = !value;
    });
}

async function refreshCounts() {
    try {
        const response = await fetch('/api/cart-wishlist-count', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;
        const data = await response.json();
        setCount('cart', data.cart_count);
        setCount('wishlist', data.wishlist_count);
    } catch {
        /* the server-rendered counts stay */
    }
}

/* ---------- Dialogs (drawer, search sheet) ---------- */
function setupDialogs() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-dialog-open]');
        if (opener) {
            const dialog = document.getElementById(opener.dataset.dialogOpen);
            if (dialog && typeof dialog.showModal === 'function') {
                dialog.showModal();
                dialog.querySelector('[autofocus], input, a, button')?.focus();
            }
            return;
        }

        const closer = event.target.closest('[data-dialog-close]');
        if (closer) {
            closer.closest('dialog')?.close();
            return;
        }

        // Click on the backdrop (the dialog element itself) closes it
        if (event.target instanceof HTMLDialogElement && event.target.open) event.target.close();
    });
}

/* ---------- Add to cart without leaving the page ---------- */
function setupAddToCart() {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-add-to-cart]');
        if (!form) return;

        event.preventDefault();
        const button = form.querySelector('[type="submit"]');
        const label = button?.querySelector('[data-label]');
        const idle = label?.textContent;

        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (label) label.textContent = 'Ajout en cours…';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                setCount('cart', data.cart_count);
                toast(data.message ?? 'Produit ajouté au panier.');
                if (label) label.textContent = 'Ajouté';
                setTimeout(() => { if (label) label.textContent = idle; }, 1800);
            } else {
                toast(data.message ?? 'Impossible d\'ajouter ce produit pour le moment.', { type: 'error' });
                if (label) label.textContent = idle;
            }
        } catch {
            toast('Connexion perdue. Réessayez dans un instant.', { type: 'error' });
            if (label) label.textContent = idle;
        } finally {
            if (button) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        }
    });
}

/* ---------- Reveal on scroll, once ---------- */
function setupReveal() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (reducedMotion() || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            // Content already scrolled past (fast jump, anchor link) must not stay hidden
            if (!entry.isIntersecting && entry.boundingClientRect.bottom > 0) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });

    items.forEach((el) => observer.observe(el));
}

// Shop filters are a disclosure on phones and a permanent sidebar from lg up
function setupFilters() {
    const panel = document.getElementById('filters');
    if (!panel) return;

    const wide = window.matchMedia('(min-width: 64rem)');
    const sync = () => { if (wide.matches) panel.open = true; };
    sync();
    wide.addEventListener('change', sync);
    panel.querySelector('summary').addEventListener('click', (event) => {
        if (wide.matches) event.preventDefault();
    });
}

// Product page: photo gallery, quantity stepper, variant price and stock, favourites
const formatFcfa = (amount) => `${Math.round(amount).toLocaleString('fr-FR').replace(/\s/g, '\u00A0')}\u00A0FCFA`;

function setupProduct() {
    const form = document.querySelector('[data-product-form]');

    const main = document.getElementById('product-main-image');
    document.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
        thumb.addEventListener('click', () => {
            if (main) main.src = thumb.dataset.galleryThumb;
            document.querySelectorAll('[data-gallery-thumb]').forEach((other) => other.removeAttribute('aria-current'));
            thumb.setAttribute('aria-current', 'true');
        });
    });

    if (form) {
        const qty = form.querySelector('[data-qty]');
        const clamp = () => {
            if (!qty) return;
            const max = parseInt(qty.max, 10) || 1;
            qty.value = Math.min(max, Math.max(1, parseInt(qty.value, 10) || 1));
        };
        form.querySelectorAll('[data-qty-step]').forEach((button) => {
            button.addEventListener('click', () => {
                qty.value = (parseInt(qty.value, 10) || 1) + parseInt(button.dataset.qtyStep, 10);
                clamp();
            });
        });
        qty?.addEventListener('change', clamp);

        // The price shown follows the chosen option; the server recomputes it anyway when adding to the cart
        form.querySelectorAll('input[name="variant_id"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                const price = document.getElementById('product-price');
                const list = document.getElementById('product-list-price');
                if (price) price.textContent = formatFcfa(radio.dataset.price);
                if (list) list.textContent = formatFcfa(radio.dataset.listPrice);
                if (qty) { qty.max = radio.dataset.stock; clamp(); }
            });
        });
    }

    document.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                const response = await fetch(document.body.dataset.wishlistUrl || '/wishlist/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                    body: JSON.stringify({ product_id: Number(button.dataset.wishlistToggle) }),
                    credentials: 'same-origin',
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message);
                button.setAttribute('aria-pressed', data.action === 'added' ? 'true' : 'false');
                setCount('wishlist', data.wishlist_count);
                toast(data.message);
            } catch {
                toast('Impossible de mettre à jour vos favoris pour le moment.', { type: 'error' });
            }
        });
    });
}

/* ---------- Cart page: change quantities, remove, clear, without reloading ---------- */
function setupCart() {
    const root = document.querySelector('[data-cart]');
    if (!root) return;

    const post = async (url, payload) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            body: JSON.stringify(payload),
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.success) throw new Error(data.message || 'Une erreur est survenue.');
        return data;
    };

    const threshold = parseFloat(root.dataset.threshold) || 0;

    const showTotals = (data) => {
        Object.entries(data.formatted ?? {}).forEach(([key, value]) => {
            const target = root.querySelector(`[data-sum="${key}"]`);
            if (target) target.textContent = value;
        });

        const text = root.querySelector('[data-free-shipping-text]');
        if (text) {
            text.textContent = data.free_shipping_remaining > 0
                ? `Plus que ${formatFcfa(data.free_shipping_remaining)} pour la livraison gratuite.`
                : 'La livraison est gratuite pour cette commande.';
        }
        const bar = root.querySelector('[data-free-shipping-bar]');
        if (bar && threshold > 0) bar.style.width = `${Math.min(100, Math.round((data.subtotal / threshold) * 100))}%`;

        if (typeof data.cart_count === 'number') setCount('cart', data.cart_count);
    };

    const update = async (line, quantity) => {
        const input = line.querySelector('[data-cart-qty]');
        const max = parseInt(input.max, 10) || 1;
        quantity = Math.min(max, Math.max(1, quantity));
        if (quantity === parseInt(input.dataset.prev, 10)) { input.value = quantity; return; }

        line.setAttribute('aria-busy', 'true');
        try {
            const data = await post(root.dataset.updateUrl, { cart_item_id: line.dataset.cartLine, quantity });
            input.value = quantity;
            input.dataset.prev = String(quantity);
            line.querySelector('[data-line-total]').textContent = data.formatted.item_subtotal;
            showTotals(data);
        } catch (error) {
            input.value = input.dataset.prev;
            toast(error.message, { type: 'error' });
        } finally {
            line.removeAttribute('aria-busy');
        }
    };

    root.addEventListener('click', async (event) => {
        const step = event.target.closest('[data-cart-step]');
        if (step) {
            const line = step.closest('[data-cart-line]');
            const input = line.querySelector('[data-cart-qty]');
            update(line, (parseInt(input.value, 10) || 1) + parseInt(step.dataset.cartStep, 10));
            return;
        }

        const remove = event.target.closest('[data-cart-remove]');
        if (remove) {
            const line = remove.closest('[data-cart-line]');
            try {
                const data = await post(root.dataset.removeUrl, { cart_item_id: line.dataset.cartLine });
                setCount('cart', data.cart_count);
                if (root.querySelectorAll('[data-cart-line]').length <= 1) { window.location.reload(); return; }
                line.remove();
                showTotals(data);
                toast(data.message);
            } catch (error) {
                toast(error.message, { type: 'error' });
            }
            return;
        }

        if (event.target.closest('[data-cart-clear]')) {
            try {
                await post(root.dataset.clearUrl, {});
                window.location.reload();
            } catch (error) {
                toast(error.message, { type: 'error' });
            }
        }
    });

    root.addEventListener('change', (event) => {
        const input = event.target.closest('[data-cart-qty]');
        if (input) update(input.closest('[data-cart-line]'), parseInt(input.value, 10) || 1);
    });
}

/* ---------- Checkout: one click only, errors announced ---------- */
function setupCheckout() {
    const form = document.querySelector('form[data-checkout]');
    if (!form) return;

    const button = form.querySelector('[data-submit]');
    const label = button?.querySelector('[data-label]');
    const idle = label?.textContent;

    form.addEventListener('submit', (event) => {
        // The browser has already validated the fields. A second submit while the first one runs is dropped.
        if (form.dataset.sending === 'true') { event.preventDefault(); return; }
        form.dataset.sending = 'true';
        if (button) { button.setAttribute('aria-disabled', 'true'); button.setAttribute('aria-busy', 'true'); button.classList.add('opacity-60', 'pointer-events-none'); }
        if (label) label.textContent = 'Enregistrement en cours…';
    });

    // Coming back with the back button restores the page as it was left: make the button usable again
    window.addEventListener('pageshow', () => {
        form.dataset.sending = 'false';
        if (button) { button.removeAttribute('aria-disabled'); button.removeAttribute('aria-busy'); button.classList.remove('opacity-60', 'pointer-events-none'); }
        if (label && idle) label.textContent = idle;
    });

    document.querySelector('[data-error-summary]')?.focus();
}

/* ---------- Payment pending page: poll the status, move on by itself ---------- */
function setupPaymentStatus() {
    const root = document.querySelector('[data-payment-status]');
    if (!root) return;

    const statusUrl = root.dataset.statusUrl;
    const pendingBox = document.getElementById('pending-box');
    const failedBox = document.getElementById('failed-box');
    const message = document.getElementById('pending-message');
    const button = document.getElementById('check-now');
    const startedAt = Date.now();
    let timer = null;
    let busy = false;

    // Every 5 s for the first 5 minutes, then every 30 s
    const nextDelay = () => (Date.now() - startedAt < 5 * 60 * 1000 ? 5000 : 30000);

    async function check() {
        if (busy) return;
        busy = true;
        clearTimeout(timer);
        if (button) button.disabled = true;

        let keepPolling = true;
        try {
            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();

            if (data.state === 'paid') {
                if (message) message.textContent = data.message;
                window.location.href = data.redirect;
                return;
            }
            if (data.state === 'failed') {
                pendingBox?.classList.add('hidden');
                failedBox?.classList.remove('hidden');
                keepPolling = false;
            } else if (message) {
                message.textContent = `${data.message} (dernière vérification à ${data.checked_at})`;
            }
        } catch {
            if (message) message.textContent = 'Connexion perdue, nouvel essai automatique…';
        } finally {
            busy = false;
            if (button) button.disabled = false;
            if (keepPolling) timer = setTimeout(check, nextDelay());
        }
    }

    button?.addEventListener('click', check);
    check();
}

/* ---------- Wishlist page: remove an item without reloading ---------- */
function setupWishlistPage() {
    const list = document.querySelector('[data-wishlist-list]');
    if (!list) return;

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-wishlist-remove]');
        if (!button) return;

        const item = button.closest('li');
        try {
            const response = await fetch(list.dataset.removeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: JSON.stringify({ product_id: Number(button.dataset.wishlistRemove) }),
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message);

            setCount('wishlist', data.wishlist_count);
            if (data.wishlist_count === 0) { window.location.reload(); return; }
            item.remove();
            toast(data.message);
        } catch {
            toast('Impossible de retirer ce produit pour le moment.', { type: 'error' });
        }
    });
}

/* ---------- Order page: check a pending mobile money payment (button + every 30 s) ---------- */
function setupOrderPaymentCheck() {
    const root = document.querySelector('[data-payment-poll]');
    if (!root) return;

    const button = document.getElementById('payment-check-button');
    const message = document.getElementById('payment-check-message');
    let busy = false;
    let timer = null;

    async function check() {
        if (busy) return;
        busy = true;
        clearTimeout(timer);
        if (button) button.disabled = true;

        let keepPolling = true;
        try {
            const response = await fetch(root.dataset.statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();

            if (data.state === 'paid' || data.state === 'failed') {
                if (message) message.textContent = data.message;
                window.location.reload();
                return;
            }
            if (message) message.textContent = `${data.message} (dernière vérification à ${data.checked_at})`;
        } catch {
            if (message) message.textContent = 'Connexion perdue, nouvel essai automatique…';
        } finally {
            busy = false;
            if (button) button.disabled = false;
            if (keepPolling) timer = setTimeout(check, 30000);
        }
    }

    button?.addEventListener('click', check);
    timer = setTimeout(check, 30000);
}

setupDialogs();
setupAddToCart();
setupReveal();
setupFilters();
setupProduct();
setupCart();
setupCheckout();
setupPaymentStatus();
setupWishlistPage();
setupOrderPaymentCheck();

// Coming back with the browser's back button restores a cached page: resync the counters
window.addEventListener('pageshow', (event) => { if (event.persisted) refreshCounts(); });
