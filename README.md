# AYP Insights — Executive Financial Dashboard

Enterprise-grade executive financial cockpit for CEO, CFO, Directors, and Finance teams. Integrates with **ERPNext v15** (`https://gmp.xtra.ayperp.net`) via REST API and optional read-only MariaDB.

Built on **Laravel 12** (Laravel 11-compatible architecture), Tailwind CSS 4, Alpine.js, ApexCharts, Sanctum, Spatie Permissions, queues, and schedulers.

## Features

| Dashboard | KPIs, charts, tables, export |
|-----------|------------------------------|
| **CEO Master** | Cash/AP/AR/expense summary, health score, traffic lights, alerts |
| **Daily Closing** | Opening/closing balance, receipts, payments, bank ledger |
| **AP** | Aging 0–30/31–60/61–90/90+, supplier outstanding |
| **AR** | Collections, recovery %, customer aging |
| **Expense** | Department/cost center, budget vs actual |

- Role-based access: CEO, CFO, Director, Finance, Branch Manager  
- REST API: `/api/dashboard/{daily-closing|ap|ar|expense|ceo}`  
- PDF/CSV export, scheduled email reports  
- Daily closing snapshots, cache rebuild jobs  
- Dark/light theme, mobile-responsive glass UI  
- Dummy financial data for demo (`ERPNEXT_USE_DUMMY_DATA=true`)

## Quick Start (XAMPP / Local)

```bash
cd "d:\xampp\htdocs\ayp insights\ayp-insights"
composer install
copy .env.example .env
php artisan key:generate
```

Create MySQL database `ayp_insights`, then update `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=ayp_insights
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate --seed
php artisan serve
```

**No Node.js?** The app works without `npm run build` — it auto-loads Tailwind CDN + `public/css/executive.css`. For production, run `npm install && npm run build` to use compiled Vite assets.

Open http://127.0.0.1:8000/login

### Demo accounts (password: `password`)

| Email | Role |
|-------|------|
| ceo@ayp-insights.local | CEO |
| cfo@ayp-insights.local | CFO |
| finance@ayp-insights.local | Finance |
| branch@ayp-insights.local | Branch Manager |

## ERPNext live integration

1. In ERPNext: **User → API Access** → generate API Key & Secret.  
2. Set `.env`:

```env
ERPNEXT_USE_DUMMY_DATA=false
ERPNEXT_URL=https://gmp.xtra.ayperp.net
ERPNEXT_API_KEY=your_key
ERPNEXT_API_SECRET=your_secret
ERPNEXT_DEFAULT_COMPANY=Your Company Name
```

3. Clear cache: `php artisan cache:clear`

Services live under `app/Services/ERPNext/` with repository bindings in `app/Providers/ERPNextServiceProvider.php`.

## API (Sanctum)

Create a token for a user:

```bash
php artisan tinker
>>> $u = User::first(); $u->createToken('dashboard')->plainTextToken;
```

```http
GET /api/dashboard/ceo?company=GMP Holdings&date=2026-05-22
Authorization: Bearer {token}
```

## Production deployment

1. **Server**: PHP 8.2+, Nginx/Apache, MariaDB 10.6+, optional Redis.  
2. `composer install --no-dev --optimize-autoloader`  
3. `php artisan migrate --force`  
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`  
5. Build assets: `npm ci && npm run build` (requires Node.js).  
6. **Queue worker**: `php artisan queue:work --daemon`  
7. **Scheduler** (cron): `* * * * * cd /path && php artisan schedule:run`  
8. Set `APP_ENV=production`, `APP_DEBUG=false`, strong `APP_KEY`.  
9. Enable HTTPS and restrict `/api` rate limits.  
10. Point `ERPNEXT_USE_DUMMY_DATA=false` with valid API credentials.

## Scheduler tasks

| Time | Job |
|------|-----|
| 05:30 daily | Rebuild dashboard cache |
| 23:55 daily | Daily closing snapshot |
| 08:00 daily | Send scheduled email reports |

## Project structure

```
app/
  Contracts/ERPNext/          # Repository interfaces
  Repositories/ERPNext/       # Dummy + live ERPNext repos
  Services/ERPNext/           # Client, Financial, AR, AP, Expense, Aggregator
  Services/Export/            # PDF & CSV
  Http/Controllers/           # Web + API dashboards
  Jobs/                       # Snapshots, cache, email
database/migrations/          # Executive tables + Spatie permissions
resources/views/dashboards/   # CEO, Closing, AP, AR, Expense
routes/web.php, api.php
```

## WhatsApp hooks

Set `WHATSAPP_WEBHOOK_URL` to your provider endpoint. `App\Services\WhatsApp\WhatsAppNotifier` posts alert payloads (dry-run logs when unset).

## License

Proprietary — AYP / GMP Executive use.
