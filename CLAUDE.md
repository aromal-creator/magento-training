# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Magento Open Source 2.4.9 (`magento/project-community-edition`) running locally in **developer mode** on PHP 8.5, with `document_root_is_pub` enabled (web root is `pub/`). Core lives in `vendor/` — never edit it; customize via modules in `app/code/` and themes in `app/design/`. Third-party Composer packages: `mageplaza/module-smtp`, `razorpay/razorpay`.

## How to work in this repo

- **Workflow:** inspect the relevant files first, explain the planned change, and get approval before modifying files or running risky commands. Afterwards, explain how to test the change, and report errors honestly.
- **Explanations:** the user is learning Magento, so explain code in beginner-friendly language.
- **Coding:** follow the Magento 2 coding standard. Use constructor dependency injection (no `new` for services, no direct `ObjectManager` use). Pick the right extension point: a plugin for wrapping a public method, an observer for reacting to an event, and a preference only when you must replace a whole class (see "Overlapping customizations" below). Before creating a module, check `app/code/Codilar/` for existing functionality.
- **Database:** use declarative schema (`etc/db_schema.xml`) for tables, and data patches for adding or updating EAV attributes and data. Explain database changes before applying them. Never drop tables, delete data, or run destructive database commands without explicit approval.
- **Secrets:** never expose passwords, API keys, or other secrets (e.g. contents of `app/etc/env.php`).

## Common commands

```bash
# After adding/changing a module, db_schema.xml, or data patch
bin/magento setup:upgrade

# After changing di.xml / constructor signatures (developer mode usually regenerates on the fly)
bin/magento setup:di:compile
rm -rf generated/code/* generated/metadata/*   # if stale generated classes cause errors

# Cache (needed after layout XML, config XML, or template changes)
bin/magento cache:flush
bin/magento cache:clean <type>

# Static content / theme work (LESS, JS, images)
rm -rf pub/static/frontend/* var/view_preprocessed/*
bin/magento setup:static-content:deploy -f

# Module management
bin/magento module:status
bin/magento module:enable Codilar_<Name>

# declarative schema whitelist (needed when adding/renaming tables/columns in db_schema.xml)
bin/magento setup:db-declaration:generate-whitelist --module-name=Codilar_<Name>
```

### Tests and linting

Test configs are in `dev/tests/{unit,integration,api-functional,static}` (copy `phpunit.xml.dist` to `phpunit.xml` before first use).

```bash
# Unit tests (single test: pass a file path or --filter)
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Codilar/<Module>/Test/Unit
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --filter testMethodName path/to/Test.php

# Coding standard
vendor/bin/phpcs --standard=Magento2 app/code/Codilar
vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run app/code/Codilar
```

No custom modules currently ship tests.

## Custom code layout

All custom modules use the `Codilar` vendor namespace in `app/code/Codilar/` (~33 modules) and are registered via `registration.php` + `etc/module.xml`; they must also be enabled in `app/etc/config.php` (done by `setup:upgrade`). They are learning/training modules, each demonstrating one Magento extension mechanism. The module name usually says what it does; group by mechanism to find examples:

- **MVC pages** (route → controller → layout handle `<frontName>_<folder>_<action>.xml` → Block/ViewModel → `.phtml`): `Student`, `StudentRegistration`, `CustomerFeedback` (`/feedback`), `ProductCreator`, `EventExample`. Storage uses declarative schema (`etc/db_schema.xml` + whitelist) with a Model/ResourceModel/Collection trio.
- **Layout injection into core pages**: `HomeProducts` (`cms_index_index`), `CategoryAttribute` (`catalog_category_view`), `OrderSuccessAlert` (`checkout_onepage_success`), `DashboardProducts` (admin dashboard).
- **Preferences** (class rewrites, mostly in `etc/frontend/di.xml`): `ContactValidation`, `FooterYear`, `GuestCart`, `HotDeal`, `LetterPricing`, `NewsletterMessage`, `PriceLabel`, `SneakerDrop`, `SortLabels`, `WishlistPaging`, `PreferenceDemo`.
- **Plugins**: `PluginDemo` (page title, product name, footer), `CartQuantityPlugin` (cart qty rules), `EventDemo` (`OrderManagementInterface`).
- **Observers**: `EventDemo`, `OrderAlert`, `OrderCoupon`, `OrderSuccessAlert`, `SneakerDrop`, `EventExample`. Custom events are dispatched by `CustomerFeedback` (`codilar_customer_feedback_save_after`, observed in `EventDemo`) and `EventExample` (`codilar_greeting_before_show`).
- **Data patches only** (run once, tracked in `patch_list`; to re-run, delete the row from `patch_list`): `AdminUser`, `ProductAttribute`, `StoreCreator`, `DemoTable` (also defines tables `codilar_demo_table`/`codilar_demo_order`), `ProductOptions`, plus patches inside `CategoryAttribute`, `OrderCoupon` (cart price rule), `SneakerDrop` (`max_per_customer` product attribute). `ProductOptions/Setup/AbstractAddCustomOptions` is a reusable base: concrete patches implement only `getSku()`/`getOptions()`. It runs under emulated adminhtml area, skips silently if the SKU is missing, and de-dupes by option title.
- **CLI**: `CategoryCreator` registers `bin/magento codilar:category:create <name> [--parent=2] [--products=SKU1,SKU2] [--inactive]` through the `CommandList` in `di.xml`.

### Overlapping customizations (check these before changing cart/product behavior)

Several modules hook into the same core classes, so a change in one can break another:
- `Magento\Checkout\Model\Cart` is replaced by a preference in `SneakerDrop` (per-customer lifetime purchase limit, `Model/PurchaseLimit`) **and** wrapped by four `CartQuantityPlugin` plugins (`ForceQuantityTwo` turns add-1 into add-2; `MaxQtyPerProduct` and `CartPageQtyCheck` enforce `QtyLimit::MAX_QTY = 2` per cart). `ForceQuantityTwo` deliberately skips products that have `max_per_customer` set.
- `Magento\Checkout\Controller\Cart\Add` is replaced by a preference in `GuestCart` (admin config `checkout/codilar_guest_cart/require_login`) and wrapped by the `CartQuantityPlugin` `AddToCartAlert` plugin.
- `Magento\Catalog\Model\Product` has a preference in both `HotDeal` and `PreferenceDemo`. Only one preference can win; `PreferenceDemo` is currently disabled in `config.php`, so enabling it would conflict with `HotDeal`. `PluginDemo` also plugs into it.
- `Magento\Theme\Block\Html\Footer` has a preference in `FooterYear` and a plugin in `PluginDemo`.
- `sales_order_place_after` is observed by both `EventDemo` and `OrderAlert`.

`MvcExample` and `StudentForm` have no `registration.php`, so Magento doesn't load them. `Hello` contains only registration files.

Themes (both inherit from `Magento/luma`):
- `app/design/frontend/Custom/storefront` — the active custom theme with LESS overrides (`web/css/source/_extend.less`, `_theme.less`) and `Magento_Theme` layout overrides.
- `app/design/frontend/Codilar/luma_child` — minimal child theme.

## Repository caveats

- `app/etc/env.php` (DB credentials, crypt key) is gitignored and must stay out of version control, along with `vendor/`, `generated/`, `var/`, `pub/static/`, `pub/media/`.


