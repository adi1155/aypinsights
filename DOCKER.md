# Docker / EasyPanel Deployment

## Quick start (local)

```bash
cp .env.example .env
# Set APP_KEY (php artisan key:generate --show)
# Set ERPNEXT_API_KEY and ERPNEXT_API_SECRET

docker compose up -d --build
docker compose exec app php artisan db:seed
```

Open: http://localhost (or the port from `APP_PORT`).

## EasyPanel

1. **New app** → **Docker Compose** → connect your Git repository.
2. **Compose file**: `docker-compose.yml`
3. **Public service**: `app` on port **80**
4. **Environment** — set at minimum:

| Variable | Example |
|----------|---------|
| `APP_KEY` | `base64:...` (from `php artisan key:generate --show`) |
| `APP_URL` | `https://insights.yourdomain.com` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_PASSWORD` | strong password |
| `MYSQL_ROOT_PASSWORD` | strong root password |
| `ERPNEXT_API_KEY` | your ERPNext API key |
| `ERPNEXT_API_SECRET` | your ERPNext API secret |

5. Point your domain in EasyPanel to the `app` service.
6. First deploy runs migrations automatically (`RUN_MIGRATIONS=true`).

## Services

| Service | Role |
|---------|------|
| `app` | Nginx + PHP-FPM (web) |
| `mysql` | Application database (MariaDB 11) |
| `redis` | Cache, sessions, queues |
| `queue` | `php artisan queue:work` |
| `scheduler` | `php artisan schedule:work` |

## Optional: ERPNext read-only DB (faster CEO dashboard)

If ERPNext MariaDB is reachable from the container network:

```env
ERPNEXT_DB_HOST=your-erpnext-db-host
ERPNEXT_DB_DATABASE=erpnext
ERPNEXT_DB_USERNAME=readonly_user
ERPNEXT_DB_PASSWORD=...
```

## Build assets (optional)

The app works without a Vite build (CDN fallback). To build frontend assets inside Docker, add a multi-stage Node step to `Dockerfile` or run locally:

```bash
npm ci && npm run build
```

## Useful commands

```bash
docker compose logs -f app
docker compose exec app php artisan migrate --force
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
```
