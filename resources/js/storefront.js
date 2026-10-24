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
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.1 });

    items.forEach((el) => observer.observe(el));
}

setupDialogs();
setupAddToCart();
setupReveal();

// Coming back with the browser's back button restores a cached page: resync the counters
window.addEventListener('pageshow', (event) => { if (event.persisted) refreshCounts(); });
