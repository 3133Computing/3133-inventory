# 3133 Inventory deployment

3133 Inventory is a 3133 Computing inventory application built from the Snipe-IT Laravel foundation. The MVP is designed to run as a Docker/Portainer stack with a MariaDB database and a persistent application storage volume.

## Production shape

- **App container:** Laravel/Apache image built from this repository's `Dockerfile`.
- **Database:** MariaDB 11.4.
- **Persistent volumes:**
  - `db_data` for MariaDB data.
  - `storage` mounted at `/var/lib/snipeit` for uploads, backups, OAuth keys, labels, and other runtime files.
- **Primary HTTP port:** published from container port `80` to the host port configured by `APP_PORT`.
- **Scheduler/queues:** Snipe-IT's container startup supervises Apache and cron. The MVP uses `QUEUE_CONNECTION=sync`; switch to a real queue later only if background jobs become important.

## Required environment variables

Use Portainer stack environment variables or a stack `.env` file. Do not commit real secrets.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:REPLACE_WITH_GENERATED_KEY
APP_URL=https://inventory.3133.ca
APP_TIMEZONE=America/Vancouver
APP_LOCALE=en-US
APP_PORT=8088

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=3133_inventory
DB_USERNAME=3133_inventory
DB_PASSWORD=REPLACE_WITH_STRONG_PASSWORD
MYSQL_ROOT_PASSWORD=REPLACE_WITH_STRONG_ROOT_PASSWORD
DB_PREFIX=null
DB_DUMP_PATH=/usr/bin
DB_DUMP_SKIP_SSL=true
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

PRIVATE_FILESYSTEM_DISK=local
PUBLIC_FILESYSTEM_DISK=local_public

MAIL_MAILER=smtp
MAIL_HOST=REPLACE_WITH_SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=REPLACE_WITH_SMTP_USER
MAIL_PASSWORD=REPLACE_WITH_SMTP_PASSWORD
MAIL_TLS_VERIFY_PEER=true
MAIL_FROM_ADDR=inventory@3133.ca
MAIL_FROM_NAME='3133 Inventory'
MAIL_REPLYTO_ADDR=inventory@3133.ca
MAIL_REPLYTO_NAME='3133 Inventory'
MAIL_AUTO_EMBED_METHOD=attachment

ALLOW_BACKUP_DELETE=false
ALLOW_DATA_PURGE=false
IMAGE_LIB=gd
BACKUP_ENV=true

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
COOKIE_NAME=3133_inventory_session
SECURE_COOKIES=true
APP_TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
LOG_CHANNEL=stderr
APP_FORCE_TLS=true
```

### Generating `APP_KEY`

For a fresh deployment, generate the key once and then keep it stable forever:

```bash
docker compose run --rm app php artisan key:generate --show
```

Changing `APP_KEY` after data exists can invalidate encrypted values and sessions.

## Portainer stack template

A production Portainer stack can build the application image directly from the checked-out repository or from a Git-backed stack. Use the same network/proxy pattern as other 3133 services.

```yaml
volumes:
  db_data:
  storage:

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: 3133-inventory:latest
    restart: unless-stopped
    volumes:
      - storage:/var/lib/snipeit
    ports:
      - "${APP_PORT:-8088}:80"
    depends_on:
      db:
        condition: service_healthy
        restart: true
    env_file:
      - stack.env

  db:
    image: mariadb:11.4.7
    restart: unless-stopped
    volumes:
      - db_data:/var/lib/mysql
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 5s
      timeout: 1s
      retries: 20
```

If the Docker host cannot build from Git in Portainer, build and push the image to a registry first, then replace the `build:` block with the pushed image tag.

## First boot checklist

1. Create or update the Portainer stack.
2. Wait for MariaDB to become healthy and for the app container to complete startup.
3. Open the app URL and complete the first-user/company setup flow.
4. Set the visible site name to **3133 Inventory** in branding settings if the installer does not do it automatically.
5. Visit `/inventory/scan` and confirm the scanner page loads.
6. Create the first sellable stock item as a Consumable and set its existing vendor barcode in `item_no`.
7. Create the first loaner item as an Asset and set its existing tracking barcode in `asset_tag`.
8. Print a stock QR label from the consumable label action and test it with the Brother P-touch workflow.

## Reverse proxy / NPM

Recommended external host: `inventory.3133.ca`.

- Proxy to the Docker host and `APP_PORT`.
- Enable WebSocket support only if needed; the MVP does not require it.
- Enable HTTPS in NPM and set `APP_URL=https://inventory.3133.ca`.
- Keep `APP_FORCE_TLS=true` and `SECURE_COOKIES=true` after TLS is working.

## Backups

Back up both volumes:

- MariaDB volume/database dump (`db_data`).
- App storage volume (`storage`) containing uploads, backups, OAuth keys, and generated files.

Minimum backup routine:

```bash
# Inside or adjacent to the stack, with credentials from the stack env
mysqldump -h db -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > 3133-inventory-$(date +%F).sql
```

Also snapshot or copy the Portainer volumes. Test restore before relying on the backup.

## Upgrade / redeploy flow

1. Push tested changes to GitHub.
2. In Portainer, pull/redeploy the stack from the selected branch/tag.
3. Watch app logs for migration or startup errors.
4. Run a browser smoke test:
   - `/`
   - `/inventory/scan`
   - barcode lookup for a known consumable
   - barcode lookup for a known loaner asset
5. Confirm backups still run after deployment.

## License and upstream attribution

3133 Inventory is derived from Snipe-IT and keeps the inherited AGPL licensing obligations. Keep upstream license and contributor attribution intact when redistributing or hosting source code. Internal 3133-specific documentation should describe this project as **3133 Inventory**, while still acknowledging the Snipe-IT foundation.
