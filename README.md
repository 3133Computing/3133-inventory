# 3133 Inventory

3133 Inventory is a custom inventory application for **3133 Computing**. It is built on the proven Snipe-IT/Laravel foundation and narrowed toward the workflows 3133 needs day-to-day: sellable stock, consumables, loaner/customer checkout items, barcode scanning, and Brother P-touch-friendly QR labels.

> This project is derived from [Snipe-IT](https://github.com/grokability/snipe-it). The upstream AGPL license and attribution remain in place; this repository documents the 3133-specific product direction and deployment workflow.

## MVP focus

The current MVP keeps Snipe-IT's stable authentication, permissions, audit logging, models, and admin foundation while adding a focused 3133 Inventory workflow layer.

### Primary workflows

- **Inventory Scan** — scan or type an existing barcode and look up the matching stock item or loaner asset.
- **Sellable Stock** — track access points, switches, smart home devices, parts, and other stock using Snipe-IT `Consumable` records keyed by `item_no`.
- **Stock Receive / Remove** — increment or decrement sellable stock with audit-log entries for stock movements.
- **Loaners / Assets** — track checkoutable equipment such as loaner APs or Home Assistant subscription boxes using Snipe-IT `Asset` records keyed by `asset_tag`.
- **Customers / Users** — use Snipe-IT users/companies as the initial customer assignment foundation.
- **QR Labels** — print browser-based QR labels for stock items, designed to work with Brother P-touch label workflows.

### Intentionally de-emphasized for MVP

The upstream Snipe-IT modules for licenses, accessories, components, kits, and heavier reports/import flows remain in the codebase but are not the main 3133 workflow. They are hidden or de-emphasized first rather than deleted, so future compatibility and migrations stay safer.

## Tech stack

- Laravel 12 / PHP 8.2+
- Blade, AdminLTE 2, Bootstrap 3
- MariaDB/MySQL in production
- PHPUnit feature tests
- Docker/Portainer deployment

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Run focused MVP tests:

```bash
php artisan test \
  tests/Feature/Inventory/InventoryScanTest.php \
  tests/Feature/Inventory/ConsumableStockMovementTest.php \
  tests/Feature/Inventory/ConsumableLabelTest.php \
  tests/Feature/DashboardTest.php
```

Run the full test suite:

```bash
php artisan test
```

## Deployment

3133's preferred production deployment target is a Portainer stack on the Docker VM.

See [docs/3133-inventory-deployment.md](docs/3133-inventory-deployment.md) for:

- required environment variables
- Portainer stack shape
- MariaDB and storage volumes
- first-boot checklist
- reverse proxy notes
- backup and restore expectations
- inherited AGPL/Snipe-IT attribution notes

A stack template is also available at [deploy/portainer-stack.yml](deploy/portainer-stack.yml).

## Key URLs after deployment

- `/` — dashboard focused on 3133 Inventory MVP workflows
- `/inventory/scan` — barcode scan and lookup page
- `/consumables` — sellable stock list
- `/hardware` — loaners/assets list
- `/users` — customers/users list

## Documentation map

- [MVP implementation plan](docs/plans/2026-05-31-3133-inventory-mvp.md)
- [Deployment guide](docs/3133-inventory-deployment.md)
- [Developer notes](CLAUDE.md)
- [Upstream license](LICENSE)
- [Upstream contributors](CONTRIBUTORS.md)

## License and attribution

3133 Inventory is a customized fork/derivative of Snipe-IT and inherits Snipe-IT's AGPL licensing obligations. Keep source availability, license text, and upstream attribution intact when distributing or hosting modified versions.

Upstream project: <https://github.com/grokability/snipe-it>
