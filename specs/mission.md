# Mission

## Purpose

**Glowing Cosmetics** is a local-market e-commerce platform for selling cosmetic products online to francophone African customers. Local payment methods are first-class citizens: MoneyFusion (mobile money / online), cash on delivery, and bank transfer.

## Target audience

| Audience | What they need |
|---|---|
| **Francophone African shoppers** | Mobile-first browsing, French-language UX and price formatting, guest checkout without registration, local payment options, clear order and payment status. |
| **Shop owner / admin staff** | A non-technical admin panel to manage products, variants, categories, orders, payment status, coupons, banners, reviews, settings and customers without a developer. |
| **Sole developer (maintainer)** | A small, conventional Laravel codebase that is easy to test, reproduce locally (Docker) and deploy by hand to Hostinger. |

## Principles

1. **Payments must reconcile.** A customer who paid must see a paid order, even if the webhook is missed. Status is verified from more than one path (webhook, callback, scheduled check, manual admin check).
2. **Checkout is low-friction.** Guest checkout stays; fields stay minimal (name, email, phone, city, neighbourhood).
3. **Mobile-first, French-first.** Locale `fr`, French price formatting, responsive layouts.
4. **Admin simplicity over flexibility.** Features are added to the existing admin panel in the existing style rather than new tools.
5. **Conventional Laravel.** Prefer framework features (migrations, queues, scheduler, mail, policies/middleware) over custom infrastructure.
6. **Nothing ships without a test.** New behaviour on checkout, payments and orders needs a feature test.

## Success looks like

- No paid order stuck in `pending`.
- Shop owner runs day-to-day operations from the admin panel alone.
- A new machine goes from `git clone` to a running app with one Docker command.

## Out of scope (for now)

- Multi-vendor marketplace or white-label resale.
- Native mobile apps.
- Multi-currency or non-francophone localisation.
