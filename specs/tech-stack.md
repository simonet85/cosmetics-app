# Tech Stack

"Current" is taken from `composer.json`, `composer.lock` and `package.json`. "Target" is the direction agreed for the constitution. Latest versions were checked on Packagist.

## Backend

| Concern | Current (in repo) | Target |
|---|---|---|
| Language | PHP ^8.2 | **PHP 8.3** (required by Laravel 13), in Docker |
| Framework | Laravel ^12.0 (locked v12.35.1) | **Laravel ^13.0** (Packagist latest v13.38.0) |
| Roles / permissions | `spatie/laravel-permission` ^6.22 (plus `AdminMiddleware`) | latest release compatible with Laravel 13 |
| Social login | `laravel/socialite` ^5.23 | latest compatible release |
| Payments | `simonet85/laravel-moneyfusion` ^1.0 (locked v1.0.3), wrapped by `App\Services\CustomMoneyFusionService` and two compatibility service providers | **`simonet85/laravel-moneyfusion` ^1.1** (Packagist v1.1.1, supports Laravel 13) used as published; the custom wrapper and providers are removed. No changes to the package for now |
| Other payments | COD, bank transfer | unchanged |
| PDF invoices | `barryvdh/laravel-dompdf` ^3.1 | latest compatible release |
| Image handling | `intervention/image-laravel` ^1.5 | unchanged |
| Mail | Laravel Mail (`OrderPaid`, `OrderStatusUpdated`); `MAIL_MAILER=log` by default | SMTP in production |
| Queue / cache / session | `database` drivers (from `.env.example`) | `database` driver, with queue and scheduler containers |
| Dev tooling | `laravel/pint`, `laravel/pail`, `laravel/tinker`, `laravel/breeze` (dev), `laravel/sail` (dev) | Pint enforced; Sail removed in favour of the project's own compose file |

## Database

- **Current:** MySQL 8.0.30 in local (Laragon) and production (Hostinger), per the README. `.env.example` and `config/database.php` default to SQLite, which contradicts the README.
- **Target:** **MySQL 8 in Docker** for development (named volume), `.env.example` aligned to it. PHPUnit runs against a separate `cosmetics_test` MySQL database in the same container (the app's migrations contain MySQL-only SQL, so SQLite cannot run them).

## Frontend

| Concern | Current | Target |
|---|---|---|
| Templates | Blade (`resources/views`: `layouts`, `shop`, `cart`, `checkout`, `account`, `admin`, `emails`) | unchanged |
| Build | Vite ^7 + `laravel-vite-plugin` ^2 | unchanged (Node in Docker) |
| CSS | Tailwind CSS ^4.1 (`@tailwindcss/vite`, `@tailwindcss/postcss`), alongside vendored Bootstrap bundle and `bootstrap-icons` | **Tailwind only** |
| JS | `axios`, vendored Bootstrap bundle, Slick Carousel, jQuery 3.6 (CDN), Font Awesome 6.4 (CDN). README mentions Alpine.js, but it is **not installed** | **No jQuery.** Vanilla JS / `fetch`; Swiper replaces Slick; no Alpine |
| Toast notifications | none consistent | **Notyf** (dependency-free). Toastr was considered but needs jQuery |
| Icons | Font Awesome (CDN) and bootstrap-icons (CDN) | one icon set, bundled through Vite |

## Testing and quality

- **Current:** PHPUnit ^11.5 with `phpunit.xml` (Unit and Feature suites, MySQL `cosmetics_test` with forced `DB_*` values, `array` mail/cache/session, `sync` queue). `tests/` contains only the two default `ExampleTest` files. `composer test` runs `php artisan test`. Only `UserFactory` exists.
- **Target:** PHPUnit feature tests for cart, checkout, MoneyFusion webhook/callback and admin order actions; model factories for the core models; Pint for formatting; Mockery / `Http::fake()` for the payment gateway; everything run through `docker compose exec`.

## Infrastructure

- **Current:** Local development on Windows with Laragon. Production on Hostinger (LiteSpeed, `.htaccess` HTTPS redirect, `ForceHttps` middleware), deployed manually via SSH and `git pull`, then `composer install --no-dev`, `npm ci && npm run build`, and artisan cache/migrate commands. No CI, no containers.
- **Target:** The existing app is **Dockerized with Docker Compose** (brownfield, no rewrite). Services: `app` (PHP 8.3-FPM), `web` (nginx), `db` (MySQL 8), `node` (Vite), plus optional `queue` and `scheduler`. GitHub Actions runs Pint and PHPUnit. Production stays on Hostinger with cron-driven `schedule:run` and a queue worker; deployment scripted.

## Conventions observed in the code

- Controllers are grouped by area: public controllers in `app/Http/Controllers`, admin ones in `Admin/`, payment ones in `MoneyFusion/`.
- Routes live in `routes/web.php`, including the webhook at `POST /api/moneyfusion/webhook`. There is no `routes/api.php`.
- Domain models in `app/Models` (Product, ProductVariant, Order, OrderItem, Coupon, Review, Cart, Wishlist, Category, Tag, Banner, Setting, and others).
- Store configuration (shipping threshold, payment methods) is stored in the `settings` table and seeded by dedicated seeders.
- There is no `app/Console` directory; schedules belong in `routes/console.php`, not `Kernel.php`.
- Code and UI strings are French-facing; follow `.editorconfig` and Pint defaults.
