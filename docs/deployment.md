# Deployment Guide

Step-by-step guide to deploying the FeRa Clinic SMS Platform to a production Linux server.

---

## Prerequisites

- Ubuntu 22.04 LTS (or similar)
- PHP 8.2+ with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `xml`, `zip`
- MySQL 8.0+
- Nginx or Apache
- Composer 2.x
- Node.js 18+ and npm (for building frontend)
- Redis (recommended for queues and cache)

---

## 1. Install PHP Extensions

```bash
sudo apt update
sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
     php8.2-curl php8.2-zip php8.2-bcmath php8.2-dom php8.2-fileinfo \
     php8.2-intl php8.2-redis redis-server
```

---

## 2. Clone Repository

```bash
cd /var/www
git clone <your-repo-url> sms-platform
cd sms-platform
```

---

## 3. Backend Setup

```bash
cd backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sms_platform
DB_USERNAME=sms_user
DB_PASSWORD=strong_password

TWILIO_ACCOUNT_SID=ACxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxx
TWILIO_MESSAGING_SERVICE_SID=MGxxxxxxx

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=mail_password
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="FeRa Clinic SMS"

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1

BCRYPT_ROUNDS=12
```

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set correct permissions:
```bash
sudo chown -R www-data:www-data /var/www/sms-platform/backend
sudo chmod -R 755 /var/www/sms-platform/backend
sudo chmod -R 775 /var/www/sms-platform/backend/storage
sudo chmod -R 775 /var/www/sms-platform/backend/bootstrap/cache
```

---

## 4. Frontend Build

```bash
cd /var/www/sms-platform/frontend
npm install
npm run build
```

The built assets are automatically placed in `backend/public/spa/`.

---

## 5. Nginx Configuration

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;

    ssl_certificate /etc/ssl/certs/your-cert.pem;
    ssl_certificate_key /etc/ssl/private/your-key.pem;

    root /var/www/sms-platform/backend/public;
    index index.php;

    # SPA fallback
    location /spa {
        try_files $uri $uri/ /spa/index.html;
    }

    # API routes
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handler
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Root redirect to SPA
    location = / {
        return 301 /spa/;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## 6. Queue Worker (Supervisor)

```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/sms-queue.conf`:
```ini
[program:sms-queue]
command=php /var/www/sms-platform/backend/artisan queue:work redis --tries=3 --timeout=60 --sleep=3
directory=/var/www/sms-platform/backend
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sms-queue.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sms-queue:*
```

---

## 7. Cron Job (Laravel Scheduler)

```bash
sudo crontab -u www-data -e
```

Add:
```
* * * * * cd /var/www/sms-platform/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Create Admin User

```bash
cd /var/www/sms-platform/backend
php artisan tinker
```

```php
App\Models\User::create([
    'name'               => 'Administrator',
    'email'              => 'admin@your-domain.com',
    'password'           => Hash::make('YourSecurePassword123!'),
    'role'               => 'admin',
    'is_active'          => true,
    'email_verified_at'  => now(),
]);
```

---

## 9. Configure Twilio Webhook

In your Twilio Console → Messaging → Services → your service:
- Status Callback URL: `https://your-domain.com/webhooks/twilio/status`
- Method: `POST`

---

## 10. Verify Deployment

```bash
# Check application is running
curl -s https://your-domain.com/api/v1/auth/me | head -50

# Check queue worker
sudo supervisorctl status

# Check logs
tail -50 /var/www/sms-platform/backend/storage/logs/laravel.log
```

Visit `https://your-domain.com/spa/` to access the platform.
