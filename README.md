<p align="center"><img src=".github/assets/readme-header.svg" alt="Veloria Vault — WordPress and WooCommerce storefront" width="100%"></p>

<p align="center">
<a href="#how-the-custom-theme-was-built">Theme</a> · <a href="#how-the-custom-plugin-works">Plugin</a> · <a href="#how-a-shopping-request-flows-through-the-system">Request flow</a> · <a href="#local-development">Local setup</a> · <a href="#build-and-test">Build &amp; test</a> · <a href="#deployment">Deployment</a> · <a href="#license">License</a>
</p>

[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.5%2B-96588A?logo=woocommerce&logoColor=white)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Version](https://img.shields.io/badge/source-1.3.1-b59a5c)](#versions)
[![License](https://img.shields.io/badge/license-Proprietary-555555)](LICENSE)
[![Stars](https://img.shields.io/github/stars/ak1458/veloriavault-legacy-wordpress?style=flat&color=b59a5c)](https://github.com/ak1458/veloriavault-legacy-wordpress/stargazers)
[![Watching](https://img.shields.io/github/watchers/ak1458/veloriavault-legacy-wordpress?style=flat&color=728775)](https://github.com/ak1458/veloriavault-legacy-wordpress/watchers)
[![Forks](https://img.shields.io/github/forks/ak1458/veloriavault-legacy-wordpress?style=flat&color=9a8464)](https://github.com/ak1458/veloriavault-legacy-wordpress/forks)
[![Last commit](https://img.shields.io/github/last-commit/ak1458/veloriavault-legacy-wordpress?color=728775)](https://github.com/ak1458/veloriavault-legacy-wordpress/commits/main/)

Veloria Vault is a custom WordPress storefront and WooCommerce commerce engine for a leather-handbag store. The local migration handoff records the move from a Next.js frontend to WordPress on 23 October 2026, followed by the theme/plugin 1.3.1 update on 24 October. This repository now holds that migrated source. The historical repository name is retained to preserve existing links; **this is the current WordPress codebase, not an archived project**.

[Storefront](https://www.veloriavault.com) · [Owner guide](docs/CLIENT-GUIDE.md) · [Source import record](docs/REPOSITORY-UPDATE-2026-11-07.md) · [Security reporting](SECURITY.md)

## Storefront preview

![Existing Veloria theme preview](wp-content/themes/veloria/screenshot.png)

*Existing screenshot bundled with the local theme; not a fresh production browser test.*

## What it includes

- A custom PHP theme with catalog, product, account, cart, classic checkout, wishlist, tracking, and policy templates.
- Tailwind CSS, Alpine.js interactions, bundled JavaScript, local fonts, and brand artwork.
- Server-side tier/prepaid offers, COD fees, coupon controls, invoice generation, and WooCommerce order integration.
- Guest order access through email OTP; cancellation review, delivery/return evidence, and guarded original-source refund flows.
- Store settings, homepage/offer/support fields, admin health checks, SEO routing, and integration controls.
- Offline regression contracts, 10,000 pricing vectors, reproducible asset builds, and checksum-producing release scripts.

Runtime code is published here. Store content, media uploads, customer records, orders, gateway credentials, and the WordPress database are managed separately and are **not included**.

## Architecture

| Path | Responsibility |
| --- | --- |
| `wp-content/themes/veloria/` | PHP templates, design assets, Tailwind/Alpine source and compiled frontend |
| `wp-content/plugins/veloria-commerce/src/` | Checkout, pricing, authentication, order/refund, shipping, admin and SEO services |
| `wp-content/plugins/veloria-commerce/tests/` | Migration, shipping/refund and pricing-parity regressions |
| `tools/` | Project, CSS/asset, business-rule, checkout and security contracts; release builder |
| `deploy/` | Explicit deployment/rollback scripts and a placeholder environment template |
| `docs/CLIENT-GUIDE.md` | Store-owner workflow guide |
| `dist/` | Generated theme/plugin ZIPs, manifest and checksums; ignored by Git |

The theme renders the storefront; WooCommerce owns the order/product model; the custom plugin applies the store rules. Storefront-specific hooks are gated by the active Veloria theme or an explicit configuration flag. WordPress core and third-party plugins are installed separately, rather than vendored in this repository.

## How the custom theme was built

The earlier Next.js storefront's design is implemented as a **custom classic WordPress theme**. PHP templates render the pages; Tailwind retains the visual styling; Alpine.js supplies browser interactions. The existing products and orders remain in WooCommerce, so the theme reads the store's data instead of creating a second catalog or order database.

| Building block | Implementation and starting point |
| --- | --- |
| Theme bootstrap | [`functions.php`](wp-content/themes/veloria/functions.php) checks for WooCommerce and Veloria Commerce, then loads setup, assets, helpers, WooCommerce integration and optional Elementor support. Missing dependencies produce an admin notice and a maintenance response for visitors. |
| Page composition | [`front-page.php`](wp-content/themes/veloria/front-page.php), [`archive-product.php`](wp-content/themes/veloria/archive-product.php) and [`single-product.php`](wp-content/themes/veloria/single-product.php) render the homepage, catalog and product pages. Reusable sections live in `template-parts/`; account, cart and checkout layouts live in `templates/`. |
| Catalog and settings | [`inc/template-helpers.php`](wp-content/themes/veloria/inc/template-helpers.php) connects the templates to plugin catalog/settings helpers. Homepage sections select WooCommerce products and reviews using the configured settings. |
| Styling and assets | `src/css/` contains the Tailwind/CSS source. [`inc/assets.php`](wp-content/themes/veloria/inc/assets.php) loads compiled assets, versions them for cache busting, and resolves bundled images or WordPress uploads with fallbacks. |
| Browser behavior | [`src/js/main.js`](wp-content/themes/veloria/src/js/main.js) registers Alpine components for the header, sliders, catalog, product options, checkout and account screens, plus shared cart, wishlist and chat stores. |
| Build pipeline | [`package.json`](wp-content/themes/veloria/package.json) copies local fonts, compiles Tailwind to `assets/dist/app.css`, and bundles JavaScript with esbuild into `assets/dist/app.js`. These outputs are committed for deployment. |

The main storefront layouts are implemented in the theme's PHP files. Optional Elementor integration is provided in [`inc/elementor.php`](wp-content/themes/veloria/inc/elementor.php); it does not mean every storefront section is editable in a page builder. See the [owner guide](docs/CLIENT-GUIDE.md) before saving an existing page through Elementor.

## How the custom plugin works

**Veloria Commerce holds the store logic separately from the visual theme.** It uses namespaced PHP classes and WordPress/WooCommerce hooks. The entry file, [`veloria-commerce.php`](wp-content/plugins/veloria-commerce/veloria-commerce.php), maps the `Veloria\Commerce` namespace to `src/`, registers activation/deactivation hooks, declares WooCommerce order-table compatibility, and calls `Plugin::boot()` on `plugins_loaded`. No Composer autoloader is required at runtime.

[`src/Plugin.php`](wp-content/plugins/veloria-commerce/src/Plugin.php) registers the settings, upgrade/cleanup hooks, CLI integration and limited REST hardening first. It then checks whether the Veloria theme is active, or whether the explicit storefront flag enables it. Storefront modules register only after that gate; WooCommerce-dependent modules also require WooCommerce to be loaded.

| Module | What it does |
| --- | --- |
| [`Pricing/DiscountEngine.php`](wp-content/plugins/veloria-commerce/src/Pricing/DiscountEngine.php) | Calculates tier/prepaid savings, coupon effects, shipping and COD amounts. The PHP implementation is checked against saved vectors generated from the earlier TypeScript engine. |
| [`Pricing/CartPricing.php`](wp-content/plugins/veloria-commerce/src/Pricing/CartPricing.php) | Reads product prices and quantities from the server cart and applies the engine through WooCommerce fee and shipping hooks. The browser's displayed total is not the source of truth. |
| [`Checkout/CheckoutHandler.php`](wp-content/plugins/veloria-commerce/src/Checkout/CheckoutHandler.php) | Adds address validation, order metadata, a total-consistency check and payment bookkeeping around WooCommerce's native checkout. |
| [`Rest/Routes.php`](wp-content/plugins/veloria-commerce/src/Rest/Routes.php) | Registers `/wp-json/veloria/v1` routes for authentication, account/order tools, checkout summaries, coupons, contact and other storefront features. |
| [`Auth/Otp.php`](wp-content/plugins/veloria-commerce/src/Auth/Otp.php) and [`Orders/OrderService.php`](wp-content/plugins/veloria-commerce/src/Orders/OrderService.php) | Provide guest verification, owned-order access, cancellation review, returns and refund orchestration. Provider confirmation and review states remain distinct from completed refunds. |
| [`Admin/`](wp-content/plugins/veloria-commerce/src/Admin/) | Provides settings, health checks, reconciliation, delivery/return controls and the easy full-refund panel for staff. |
| [`Shipping/ShiprocketSync.php`](wp-content/plugins/veloria-commerce/src/Shipping/ShiprocketSync.php) | Registers the optional custom shipping-sync jobs. It must be configured consistently with the official integration to avoid duplicate importers. |

## How a shopping request flows through the system

1. **Page load:** WordPress chooses a Veloria PHP template. The template reads product/catalog data and store settings, then returns HTML with the compiled stylesheet and JavaScript bundle.
2. **Cart interaction:** Alpine handles the drawer and quantity controls. [`stores/cart.js`](wp-content/themes/veloria/src/js/stores/cart.js) sends cart operations to WooCommerce's Store API; the returned server cart updates the shared UI state.
3. **Price calculation:** WooCommerce recalculates the cart. Veloria's pricing hooks use the current products, quantities, gateway and settings to calculate offers and fees. Switching the checkout payment method requests refreshed totals.
4. **Order submission:** The classic WooCommerce checkout creates the order and manages its native stock, email and gateway integration. Veloria adds validation, metadata and total checks. The custom `/checkout/summary` route supplies a summary; it does not replace the native order-creation flow.
5. **Payment and later actions:** The configured payment gateway handles payment processing. Veloria's payment hooks update its bookkeeping; later cancellation/return/refund requests pass through the owned-order services and their review/provider checks.
6. **Authenticated account tools:** The theme's [`lib/api.js`](wp-content/themes/veloria/src/js/lib/api.js) calls the plugin REST routes and obtains fresh session nonces for cached pages. Login or verified guest access determines which orders the caller may use; submitting an email address alone does not grant access.

### Where to make changes

Change layouts in the theme's PHP/templates, appearance in `src/css/`, and client interactions in `src/js/`; then rebuild the theme assets. Change pricing in the plugin's pricing classes and run its parity/business-rule checks. Change order or refund behavior in the plugin services and run the corresponding regression contracts. Store-owner content and configured values belong in WordPress/WooCommerce settings, as described in the [owner guide](docs/CLIENT-GUIDE.md).

## Local development

Requirements from the source headers: WordPress 6.5+, PHP 8.1+, WooCommerce 8.5+. The reproducible build pins Node.js **24.19.0**. These are source requirements, not a claim that all newer releases have been acceptance-tested.

1. Create a separate local WordPress installation and database using synthetic data.
2. Install WooCommerce and the required official integrations for the flow you are testing. Use classic cart/checkout; WooCommerce Blocks checkout is unsupported by this engine.
3. Place the `veloria` theme and `veloria-commerce` plugin in their corresponding `wp-content` folders. Activate the plugin, then the theme in the isolated site.
4. Configure local URLs and test gateway credentials outside Git. Disable real outbound mail, payment and courier activity in the development environment.
5. Install build dependencies from the repository root:

```sh
make setup
```

This source is not a complete installable copy of the live store. It does not contain a database dump, WordPress core, or the third-party plugin collection. Do not import local data over a production database.

## Build and test

```sh
# Linux, macOS, or a suitable Unix build environment with Node, PHP and make
make test

# Container alternative
docker build -t veloria-wordpress-test .
docker run --rm --network none veloria-wordpress-test

# Upload-ready theme/plugin ZIPs; requires the build tools in the Dockerfile
make build-release
```

`make test` covers theme tests, asset compilation, PHP contracts and lint, order/refund/shipping regressions, and pricing parity. `tools/build-release.sh` builds the assets, checks PHP and parity, and writes two ZIPs plus `SHA256SUMS` and `manifest.txt` into `dist/`. Run the full test contract separately before approving a release.

Built theme CSS/JS and font files are tracked so the web server does not need Node.js. Dependency directories remain excluded.

## Configuration

Secrets belong in the installation's private configuration or official gateway settings. The source supports these configuration names:

| Name | Purpose |
| --- | --- |
| `VELORIA_RAZORPAY_KEY_ID`, `VELORIA_RAZORPAY_KEY_SECRET` | Payment configuration; official gateway settings are the alternative |
| `VELORIA_SHIPROCKET_EMAIL`, `VELORIA_SHIPROCKET_PASSWORD` | Optional custom shipping integration; avoid duplicate importers |
| `VELORIA_CLIENT_IP_HEADER` | Trusted proxy header, only when the proxy overwrites it |
| `VELORIA_NOINDEX`, `WP_ENVIRONMENT_TYPE` | Isolated staging environment and indexability |

Store settings use the `veloria_commerce_settings` option. Configure SMTP separately for `wp_mail`. Do not commit `wp-config.php`, environment files, private keys, uploads, or backups.

## Deployment

Only deploy generated theme/plugin ZIPs, through the explicit scripts. Copy `deploy/deploy.env.example` to a private environment file and configure the intended target. The cron script contains placeholder home paths; adapt them privately for that installation.

```sh
# Separate staging installation only
bash deploy/deploy.sh deploy/staging.env

# File rollback from a verified backup; does not automatically restore the database
bash deploy/rollback.sh deploy/staging.env
```

The deployment script includes a production confirmation flag, but publishing this repository is **not a production deployment**. Production work requires its own approved window and fresh verified backup. Do not run page seeders, refunds, shipping actions, or database restores as part of a source-code sync.

## Versions

The theme `style.css`, plugin header and plugin constant identify source version **1.3.1**. The theme's npm manifest still says **1.2.0** because it describes build tooling; it is not the installed WordPress package version. Those values were retained from the local source.

Historical WordPress/core/plugin snapshots remain in Git history. No new binary release or package is implied by this import. Build fresh ZIPs and verify their checksums before deployment.

## Operational limits

The local handoff records deployment in October, but this import does not independently revalidate today's live installation. Real provider settlement, callback/email delivery, courier handling, and concurrent payment/refund acceptance remain separate operational checks. The recorded policy uses eligible 48-hour cancellation review and seven-day returns after verified delivery; current configured rules and provider evidence govern actual operations.

If styles are missing, rebuild assets and verify the theme resolver paths. If email OTP does not arrive, inspect local mail capture or the installation's SMTP configuration. For pricing differences, run the parity and business-rule contracts before changing checkout code. See the [owner guide](docs/CLIENT-GUIDE.md) for staff workflows.

## License

The repository retains its existing [proprietary license](LICENSE), and the theme header is also marked Proprietary. Public visibility does not grant reuse rights. Third-party WordPress/WooCommerce components, fonts and frontend dependencies retain their own licenses; see [third-party notices](THIRD-PARTY-NOTICES.md). Developer credits in the source remain Arranto / Smile Fotilo.
