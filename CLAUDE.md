# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **WooCommerce payment gateway plugin** for ePayco (a Colombian payment processor). It integrates ePayco's checkout system into WooCommerce stores. Current version: **8.4.4**.

Requirements: WordPress 6.8.3+, WooCommerce 8.4.0+.

## Development Setup

This is a pure PHP WordPress plugin with **no build process, no composer, no npm**. Development requires:
- A local WordPress installation with WooCommerce active
- Place/symlink this directory into `wp-content/plugins/woocommerce-gateway-payco/`
- Activate via WordPress admin or WP-CLI: `wp plugin activate woocommerce-gateway-payco`

To test:
- Configure at **WooCommerce → Settings → Checkout → ePayco**
- Enable test mode via the `test` setting toggle
- Use ePayco sandbox credentials (P_CUST_ID_CLIENTE, P_KEY, PUBLIC_KEY, PRIVATE_KEY)

## Architecture

### Entry Point & Bootstrap

**`woocommerce-gateway-payco.php`** — Plugin header and bootstrap. Defines constants (`EPAYCO_WOOCOMMERCE_VERSION`, `EPAYCO_PLUGIN_URL`, `EPAYCO_PLUGIN_DIR`), hooks into `plugins_loaded` to initialize the gateway, registers 10 custom order statuses (prod + test variants), and schedules cron jobs.

### Core Classes

- **`classes/class-wc-gateway-epayco.php`** — Main gateway class extending `WC_Payment_Gateway`. Handles `process_payment()`, `receipt_page()`, `generate_epayco_form()`, and the validation callback `validate_ePayco_request()`. ~1,237 lines.
- **`classes/class-wc-transaction-epayco.php`** — `Epayco_Transaction_Handler`: processes transaction state codes and updates WooCommerce order statuses. Called by the validation callback.
- **`classes/epayco-settings.php`** — Defines the settings form fields shown in WooCommerce admin.
- **`includes/blocks/wc-gateway-epayco-support.php`** — WooCommerce Blocks (block checkout) integration.
- **`includes/blocks/EpaycoOrder.php`** — Manages the `wp_epayco_order` custom DB table for tracking payment attempts and stock changes.

### Payment Flow

```
process_payment() → redirect to receipt page
    ↓
receipt_page() → generate_epayco_form()
    → POST to apify.epayco.co/payment/session/create (gets session token)
    → Renders ePayco Checkout v2 (checkout-green-v2.js)
    ↓
Customer pays on ePayco platform
    ↓
Webhook/confirmation → validate_ePayco_request()
    → Verifies SHA256 signature: SHA256(P_CUST_ID ^ P_KEY ^ x_ref_payco ^ x_transaction_id ^ x_amount ^ x_currency_code)
    → Fetches transaction from apify.epayco.co/payment/transaction
    ↓
Epayco_Transaction_Handler::handle_transaction()
    → State 1 = Approved, 2/4/10/11 = Cancelled/Failed, 3/7 = Pending, 6 = Reversed
    → Updates WC order status, manages stock, saves metadata
```

### API Endpoints

- `https://apify.epayco.co/login` — Bearer token auth
- `https://apify.epayco.co/payment/session/create` — Create checkout session
- `https://apify.epayco.co/payment/transaction` — Get transaction status
- `https://apify.epayco.co/transaction/detail` — Get pending orders
- `https://secure.epayco.co/validation/v1/reference/{ref}` — Validate by reference

### Cron Jobs

Two recurring jobs sync order statuses with ePayco:
- **5-minute job** (`woocommerc_epayco_cron_job_funcion`): syncs on-hold/pending WC orders
- **Hourly job**: uses WooCommerce Action Scheduler

### Custom Order Statuses

The plugin registers both production and test variants of: `epayco-failed`, `epayco-cancelled`, `epayco-on-hold`, `epayco-processing`, `epayco-completed` (prefixed `wc-`).

### Database

Custom table `wp_epayco_order` tracks: `id_payco`, `order_id`, `order_stock_restore`, `order_stock_discount`, `order_status`. Managed by `EpaycoOrder` class, created on plugin activation.

## Key Settings (epayco-settings.php)

| Setting | Purpose |
|---|---|
| `P_CUST_ID_CLIENTE` | Customer ID for signature |
| `P_KEY` | Secret key for signature verification |
| `PUBLIC_KEY` / `PRIVATE_KEY` | API authentication |
| `test` | Toggle test/production mode |
| `checkout_type` | `onpage` (embedded) vs `standard` (redirect) |
| `stock_actions` | Whether to reduce stock on pending payment |
| `ep_query_orders` | Enable cron-based order sync |