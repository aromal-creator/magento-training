# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Magento Open Source 2.4.9 (`magento/project-community-edition`) running locally in **developer mode** on PHP 8.5, with `document_root_is_pub` enabled (web root is `pub/`). Core lives in `vendor/` — never edit it; customize via modules in `app/code/` and themes in `app/design/`. Third-party Composer packages: `mageplaza/module-smtp`, `razorpay/razorpay`.

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

All custom modules use the `Codilar` vendor namespace in `app/code/Codilar/` and are registered via `registration.php` + `etc/module.xml`; they must also be enabled in `app/etc/config.php` (done by `setup:upgrade`). These are mostly learning/training modules following standard Magento MVC: frontend route (`etc/frontend/routes.xml`) → controller (`Controller/<Folder>/<Action>.php`) → layout handle `<frontName>_<folder>_<action>.xml` → Block → `.phtml` template.

- **ProductCreator** (`/productcreator/product/{index,create}`) — creates catalog products programmatically and logs them in its own table via a Model/ResourceModel/Collection trio (`etc/db_schema.xml`).
- **ProductOptions** — data patches only. `Setup/AbstractAddCustomOptions` is a base `DataPatchInterface` that adds priced custom options to a single SKU (runs under emulated adminhtml area, skips silently if SKU missing, de-dupes by option title). Concrete patches in `Setup/Patch/Data/` just implement `getSku()` and `getOptions()`. Patches run once — tracked in the `patch_list` table.
- **StudentRegistration** (`/studentregistration`) — form + `Save` controller persisting to `codilar_student_registration` (declarative schema).
- **CustomerFeedback** (`/feedback`) — feedback form (name, email, 1–5 rating, message) saved to `codilar_customer_feedback`. Validates the form key and fields in `Controller/Index/Save`. `ViewModel/Form` refills the form after a failed submit (DataPersistor) or prefills it for logged-in customers, so the form block is `cacheable="false"`.
- **Student** (`/student`), **MvcExample**, **Hello** — simple MVC/registration examples. `StudentForm` is an empty skeleton (no `registration.php`, not enabled).

Themes (both inherit from `Magento/luma`):
- `app/design/frontend/Custom/storefront` — the active custom theme with LESS overrides (`web/css/source/_extend.less`, `_theme.less`) and `Magento_Theme` layout overrides.
- `app/design/frontend/Codilar/luma_child` — minimal child theme.

## Repository caveats

- `app/etc/env.php` (DB credentials, crypt key) is gitignored and must stay out of version control, along with `vendor/`, `generated/`, `var/`, `pub/static/`, `pub/media/`.
