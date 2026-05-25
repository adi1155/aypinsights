# Production Deployment Guide — AYP Insights

## 1. Server requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`
- MariaDB 10.6+ (application DB)
- Nginx or Apache with `public/` as document root
- Supervisor or systemd for queue workers
- Cron for Laravel scheduler
- Optional: Redis for cache/sessions/queues

## 2. Environment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://insights.yourdomain.com

DB_CONNECTION=mysql
DB_DATABASE=ayp_insights
DB_USERNAME=ayp_app
DB_PASSWORD=strong_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

ERPNEXT_USE_DUMMY_DATA=false
ERPNEXT_URL=https://gmp.xtra.ayperp.net
ERPNEXT_API_KEY=...
ERPNEXT_API_SECRET=...
```

## 3. Deploy commands

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
php artisan storage:link
```

## 4. Nginx snippet

```nginx
server {
    listen 443 ssl http2;
    server_name insights.yourdomain.com;
    root /var/www/ayp-insights/public;

    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 5. Supervisor (queue)

```ini
[program:ayp-insights-queue]
command=php /var/www/ayp-insights/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
```

## 6. Cron

```
* * * * * www-data cd /var/www/ayp-insights && php artisan schedule:run >> /dev/null 2>&1
```

## 7. Security checklist

- [ ] HTTPS only, HSTS enabled  
- [ ] Firewall ERPNext DB read-only user to app server IP only  
- [ ] Rotate API keys quarterly  
- [ ] Rate limit `/api/*`  
- [ ] Disable directory listing  
- [ ] File permissions: `storage/` and `bootstrap/cache/` writable by web user only  

## 8. Health check

`GET /up` — Laravel built-in health route.
