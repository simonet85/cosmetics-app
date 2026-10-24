# Roadmap

Source: `.docs/IMPLEMENTATION-TODO.md` (there is no `TODO.md`), plus the stack gaps in [tech-stack.md](tech-stack.md). Phases are deliberately small: each should be shippable on its own with its tests.

Order: Dockerize the existing app, get a safety net of tests, upgrade, then payment reconciliation, UX, and cleanup.

## Phase 0 — Dockerize the brownfield app (docker compose)

Goal: `git clone` + `docker compose up` gives a working app, without changing application code.

- [x] 0.1 `Dockerfile` (PHP 8.3 CLI/FPM with the extensions in the README: pdo_mysql, mbstring, xml, bcmath, gd, intl, zip) and `.dockerignore`.
- [x] 0.2 `docker-compose.yml` with services `app` (PHP), `web` (nginx), `db` (MySQL 8 with a named volume), `node` (Vite dev server).
- [x] 0.3 Align `.env.example` with the compose services (`DB_CONNECTION=mysql`, `DB_HOST=db`, `APP_LOCALE=fr`, MoneyFusion variables).
- [x] 0.4 Entrypoint step: `composer install`, `key:generate` if missing, `migrate`, `storage:link`.
- [~] 0.5 Optional `queue` and `scheduler` services (`queue:work`, `schedule:work`), reused later by Phase 2. (services exist behind the `workers` profile; not yet exercised)
- [x] 0.6 Seed with `db:seed` inside the container; confirm the home page, shop, cart and admin load.
- [x] 0.7 Run PHPUnit and Pint through compose (`docker compose exec app composer test`, `vendor/bin/pint`).
- [x] 0.8 Document the Docker workflow in the README and correct its stack claims (Alpine, DB, Laragon).

## Phase 1 — Safety net

- [x] 1.1 Run Pint over the codebase and commit the formatting baseline.
- [x] 1.2 Factories for Product, Order, OrderItem, Coupon, Category (ProductVariant still missing).
- [x] 1.3 Feature test: guest cart add/update/remove.
- [x] 1.4 Feature test: checkout creates an order (COD) (18 tests: totals, tax and shipping from settings, stock, addresses, validation, rollback).
- [x] 1.5 Feature test: MoneyFusion webhook marks an order paid (fake HTTP) (20 webhook tests + 4 MoneyFusion checkout tests; the webhook now verifies with the gateway).
- [x] 1.6 (CI on stand-by: PR #1 is open but GitHub refuses to start jobs while the account has a billing lock; re-run the jobs once it is lifted) GitHub Actions workflow running Pint and PHPUnit (`.github/workflows/ci.yml`: Pint job; tests job on MySQL 8 with the Vite build, checked with actionlint and by running the suite without the dev server).

## Phase 2 — Upgrade to Laravel 13 and stock MoneyFusion package

Packagist shows `laravel/framework` v13.x (requires PHP ^8.3) and `simonet85/laravel-moneyfusion` v1.1.1 (supports Laravel 13). The app is on Laravel 12.35.1 and package v1.0.3.

- [ ] 2.1 Bump the Docker image to PHP 8.3 (already the Phase 0 baseline).
- [ ] 2.2 Update `composer.json` to `laravel/framework:^13.0`; update first-party packages (Socialite, Tinker, Breeze, Sail, Pint, Pail, PHPUnit, Collision) and resolve conflicts; follow the official 12 → 13 upgrade guide.
- [ ] 2.3 Run the Phase 1 test suite and fix breakages.
- [x] 2.4 Upgrade to `simonet85/laravel-moneyfusion:^1.1` and use the package as published. Done early: `CustomMoneyFusionService`, `CustomMoneyFusionServiceProvider` and `MoneyFusionCompatibilityServiceProvider` are removed; the app webhook extends the package's verifying `WebhookController` and only overrides its order hooks (through `App\Services\OrderPayments`).
- [ ] 2.5 Re-run the webhook and callback tests, then smoke-test a sandbox payment.

## Phase 3 — Payment reconciliation (TODO phase 1)

- [x] 3.1 `moneyfusion:check-pending` command (`--age`, `--limit`, `--expire-after`, `--pause`): asks the gateway, applies outcomes through `OrderPayments`, progress bar and summary table.
- [x] 3.2 Tests for the command (paid, failed, cancelled, still pending, too young, limit, gateway down, wrong amount, report): `CheckPendingPaymentsCommandTest`.
- [x] 3.3 Schedule in `routes/console.php`: every 10 minutes, `withoutOverlapping`.
- [x] 3.4 Nightly sweep (03:00, larger batch). Payments still pending after 24 h are treated as abandoned: order cancelled, stock released; a late payment revives the order.
- [x] 3.5 Callback verifies status live before redirecting, falling back to local data on API error.
- [x] 3.6 Production: cron `schedule:run` every minute and the required MoneyFusion variables documented in the README (Hostinger cron entry); Docker `workers` profile runs the scheduler locally.

## Phase 4 — Payment UX (TODO phase 2)

- [x] 4.1 JSON status endpoint `GET /payment/status/{order}` (`PaymentStatusController`): owner or signed link, rate limited, asks MoneyFusion at most once per 10 s per payment, states paid / pending / failed / unavailable.
- [x] 4.2 "Vérifier maintenant" button on the customer order page (plain `fetch`, no reload).
- [x] 4.3 30-second auto-poll while the payment is pending; the page reloads once the payment is confirmed or failed.
- [x] 4.4 `AdminOrderController::checkPayment` and `POST admin/orders/{order}/check-payment`, through the shared `PaymentVerifier`.
- [x] 4.5 "Vérifier le paiement" button on the admin order page, with the MoneyFusion token, creation date and local status; hidden once paid.
- [x] 4.6 `checkout.pending` page (signed link or owner): polls every 5 s, redirects to the confirmation once paid, shows a retry box on failure; the payer's return from the gateway lands there while the payment is pending.

## Phase 5 — Notifications (TODO phase 3)

- [x] 5.1 Payment-confirmed email: `OrderPaid` with the invoice PDF attached, sent once by `OrderPayments::markPaid` (webhook, return page, command or admin check).
- [x] 5.2 Payment-failed email (`PaymentFailed`): declined, cancelled or expired reasons, retry link, "do not pay twice" notice; sent once when the order is cancelled for its payment.
- [x] 5.3 Pending-too-long reminder (`PaymentPending`): sent once by `moneyfusion:check-pending` (`--remind-after=60`), signed link to the pending page and the time left before automatic cancellation.
- [x] 5.4 Tests with `Mail::fake()` for all three (`PaymentEmailsTest`).

## Phase 6 — Frontend consolidation (no jQuery)

- [ ] 6.1 Add Notyf (dependency-free toast library) via npm and Vite; show flash messages through it.
- [ ] 6.2 Replace Slick with Swiper on the home and product carousels.
- [ ] 6.3 Replace jQuery usage in the views with vanilla JS / `fetch`; remove the jQuery CDN script.
- [ ] 6.4 Replace Bootstrap JS usage (modals, dropdowns) with Tailwind-based markup, then remove the vendored Bootstrap bundle and `bootstrap-icons`.
- [ ] 6.5 Keep a single icon set; bundle it through Vite instead of a CDN.

## Phase 7 — Optional improvements (TODO phase 4)

- [ ] 7.1 `CheckMoneyFusionPaymentJob` with retries and backoff.
- [ ] 7.2 Admin payments dashboard (stats, filters, CSV export).
- [ ] 7.3 `moneyfusion:report` command.
- [ ] 7.4 Sanctum-protected payment-check API endpoint.

## Phase 8 — Deploy automation

- [~] 8.1 Deploy script `scripts/deploy.sh` (dry-run mode, local asset build because the server has no Node, maintenance mode, database dump, fast-forward pull, migrations, caches, smoke tests, rollback hints) written and linted; **not run yet**. `scripts/set-remote-db-password.sh` fixes the stale DB password in the production .env (to be run by the owner).
- [ ] 8.2 CI deploy job triggered from `main` once tests pass.

## Deferred

- **Contributing to `simonet85/laravel-moneyfusion`** (TODO phase 5: optional routes, webhook path, URL helper). Skipped for now: the app uses the Packagist release as is.

## Notes

- The TODO file references `app/Console/Kernel.php`; Laravel 12 and 13 have no such file, so scheduling goes in `routes/console.php`.
- The TODO also references `MONEYFUSION-CHECK-PAYMENT.md` and `MONEYFUSION-IMPLEMENTATION.md`, which are **not in the repo**. The code samples must be rewritten when those phases start.

## On hold (production follow-ups, paused on 2026-10-24)

Production (klab-consulting.com) runs the code of phases 0 to 5; the database password in its `.env` was fixed and the site answers 200. To pick up later:

- [ ] Real MoneyFusion test payment (250 F minimum): confirm whether the gateway's `Montant` is net or gross of fees. The package refuses a paid status when it differs from the amount sent, so a net amount would leave legitimate payments pending.
- [ ] Set `APP_DEBUG=false` in the production `.env` (it is `true`).
- [ ] Create the hPanel cron job: `* * * * * cd ~/domains/klab-consulting.com/laravel && php artisan schedule:run` (nothing scheduled runs until then).
- [ ] 7 legacy MoneyFusion orders are still pending and `moneyfusion_payments` is empty: backfill payment rows from `orders.moneyfusion_token` so the scheduled check can settle or expire them.
- [ ] Tidy the server: `public/build.prev/`, `.env.bak-20261021` (mode 755), `.env` itself (mode 755).
- [ ] GitHub Actions is blocked by an account billing lock; re-run the CI jobs once it is lifted.
