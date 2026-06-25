# Production Checklist

Use this checklist before going live with the FeRa Clinic SMS Platform.

---

## Environment

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_URL` set to actual domain
- [ ] HTTPS configured (SSL certificate installed)
- [ ] `.env` file permissions set to `600` (owner-only read)
- [ ] `.env` is in `.gitignore` and not committed to version control

## Database

- [ ] MySQL 8.0+ running
- [ ] `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` configured
- [ ] `php artisan migrate --force` executed
- [ ] Admin user created (`php artisan tinker` → `User::create(...)`)
- [ ] Database user has only SELECT/INSERT/UPDATE/DELETE/CREATE TABLE privileges (not SUPER/ROOT)
- [ ] Automated daily backups configured (see [Backup Guide](./backup.md))
- [ ] Backup restore tested

## Twilio

- [ ] `TWILIO_ACCOUNT_SID` set
- [ ] `TWILIO_AUTH_TOKEN` set
- [ ] `TWILIO_MESSAGING_SERVICE_SID` set
- [ ] Webhook URL configured in Twilio Console: `https://your-domain/webhooks/twilio/status`
- [ ] Twilio webhook signature validation verified
- [ ] Geo permissions configured for target countries
- [ ] Sender ID / Alpha Sender approved for target markets (if applicable)
- [ ] Test SMS sent successfully

## Queue

- [ ] `QUEUE_CONNECTION=redis` (recommended) or `database`
- [ ] Queue worker running as a supervised process (see [Queue Guide](./queues.md))
- [ ] Supervisor or systemd service configured to restart worker on failure
- [ ] `php artisan queue:work --tries=3 --timeout=60` command tested

## Mail (for OTP and Admin Alerts)

- [ ] `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` configured
- [ ] `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` set
- [ ] Test email received (`php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com')->subject('Test'))`)
- [ ] OTP email received in test login flow

## Security

- [ ] `BCRYPT_ROUNDS=12` set in `.env`
- [ ] Session timeout configured (default 480 min in System Settings)
- [ ] Admin accounts use strong passwords (12+ chars, mixed case, symbols)
- [ ] 2FA (OTP) verified working
- [ ] All admin email addresses verified
- [ ] `php artisan config:cache` run
- [ ] `php artisan route:cache` run
- [ ] `php artisan view:cache` run

## Frontend

- [ ] `npm run build` executed successfully
- [ ] Built assets are in `backend/public/spa/`
- [ ] SPA loads correctly at `https://your-domain/spa/`
- [ ] All pages tested for admin role
- [ ] All pages tested for staff role (restricted access verified)

## Monitoring

- [ ] System Monitoring page accessible and green
- [ ] Admin alert email configured in System Settings → Notifications
- [ ] Admin accounts will receive alert emails
- [ ] Log file rotation configured (`/storage/logs/laravel.log`)
- [ ] Server uptime monitoring configured (e.g., UptimeRobot, Pingdom)

## Cron Job

- [ ] Laravel scheduler added to server crontab:
  ```
  * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Scheduled tasks verified (`php artisan schedule:list`)

## Final Verification

- [ ] Create a test contact
- [ ] Create a test campaign with 1 contact
- [ ] Send campaign successfully
- [ ] Receive SMS on test number
- [ ] Verify delivery status updates via webhook
- [ ] Verify click tracking works
- [ ] Check audit log for campaign creation and send events
- [ ] Verify phone numbers are masked for staff users
- [ ] Verify staff cannot access /users, /monitoring, /audit-logs, /settings (system tabs)
