# Queue & Workers Guide

The FeRa Clinic SMS Platform uses Laravel Queue for all campaign sending.

---

## How It Works

```
CampaignController::sendNow()
    └── dispatch(DispatchCampaignJob)
            └── chunks through eligible contacts (500 at a time)
                    └── dispatch(SendSmsJob) with staggered delay
                            └── TwilioService::sendMessage()
                            └── Updates Message status
                            └── CampaignCompletionService::checkAndComplete()
```

---

## Queue Driver Options

### Option 1: Database (Default — Simple)

```env
QUEUE_CONNECTION=database
```

- Stores jobs in `jobs` table in MySQL
- Simple to set up, no extra services
- Good for low/medium volume (< 10,000 SMS/day)
- Slower than Redis

### Option 2: Redis (Recommended for Production)

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

- Much faster job processing
- Better visibility into queue depth
- Recommended for > 10,000 SMS/day

---

## Starting the Worker

### Development

```bash
php artisan queue:work --tries=3 --timeout=60
```

### Production with Supervisor (Recommended)

Create `/etc/supervisor/conf.d/sms-queue.conf`:

```ini
[program:sms-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/backend/artisan queue:work database --tries=3 --timeout=60 --sleep=3 --max-jobs=500
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sms-queue.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sms-queue:*
```

### Production with systemd

Create `/etc/systemd/system/sms-queue.service`:

```ini
[Unit]
Description=SMS Platform Queue Worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/path/to/backend
ExecStart=/usr/bin/php artisan queue:work --tries=3 --timeout=60 --sleep=3
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable sms-queue
sudo systemctl start sms-queue
```

---

## Monitoring Queues

### Via System Monitoring page

Visit `/monitoring` in the platform — shows pending jobs, failed jobs, recent failures.

### Via artisan

```bash
# Check pending jobs
php artisan queue:monitor

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush

# List failed jobs
php artisan queue:failed
```

---

## Failed Jobs

Failed jobs are stored in the `failed_jobs` table. The System Monitoring page shows recent failures with error messages.

To retry a specific job:
```bash
php artisan queue:retry {id}
```

To retry all failed jobs:
```bash
php artisan queue:retry all
```

---

## Rate Limiting

SMS sending is rate-limited by `SMS_RATE_LIMIT_DELAY` (milliseconds between each message). Default: 1000ms (1 SMS/second).

This can be adjusted in System Settings → Twilio → Send Rate Limit.

Twilio's per-number limits:
- US long code: 1 message/second
- US short code: 100 messages/second
- UK number: varies

---

## Scheduled Tasks

Add to crontab:
```
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Current scheduled tasks:
- None currently defined (add in `routes/console.php` as needed)

---

## Troubleshooting

**Jobs stuck in "pending" status:**
- Queue worker not running → Start worker with supervisor
- Worker crashed → Check supervisor logs

**High failed job count:**
- Check failed_jobs table for error messages
- Visit System Monitoring page for recent failure details
- Common causes: Twilio credentials wrong, phone format invalid, contact opted-out

**Campaign stuck in "sending":**
- Worker may have crashed mid-send
- Run: `php artisan queue:retry all`
- Check if campaign has messages still in `pending`/`queued` status
