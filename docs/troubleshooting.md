# Troubleshooting Guide

Common issues and how to resolve them.

---

## Authentication Issues

### OTP email not received
1. Check mail configuration: `php artisan tinker` → send a test email
2. Check spam folder
3. Verify `MAIL_FROM_ADDRESS` is a valid sending address
4. Check `storage/logs/laravel.log` for mail errors

### Login blocked after multiple attempts
- Rate limit is 5 attempts per minute (IP-based)
- Wait 60 seconds or change IP
- To clear rate limit: `php artisan cache:clear`

### "Account is inactive"
- User has `is_active = false` in the database
- Admin must go to Users page and activate the account

---

## SMS Sending Issues

### SMS not being sent
1. Check queue worker is running: `supervisorctl status sms-queue`
2. Check `failed_jobs` table: `php artisan queue:failed`
3. Check Twilio credentials in `.env`
4. Check campaign status — may be stuck in "sending"

### Twilio error codes
| Code | Meaning | Resolution |
|------|---------|------------|
| 21408 | Permission not enabled for region | Enable Geo Permissions in Twilio Console |
| 21614 | "To" number is not valid | Phone number format issue |
| 21211 | Invalid "To" phone number | Phone number doesn't exist |
| 30003 | Unreachable destination handset | Handset off or out of service area |
| 30004 | Message blocked | Number on do-not-contact list |
| 30005 | Unknown destination handset | Number not in service |
| 30007 | Carrier violation | Content filtered by carrier |
| 30008 | Unknown error | Retry; if persists contact Twilio |

### Campaign stuck in "sending"
```bash
# Check if worker is running
php artisan queue:monitor

# Retry any failed jobs for the campaign
php artisan queue:retry all

# Manually complete a stuck campaign (emergency use only)
php artisan tinker
Campaign::find(ID)->update(['status' => 'completed'])
```

### High undelivered rate
- Check destination country Geo Permissions in Twilio Console
- Verify message content complies with carrier policies
- Check suppression list for commonly failing numbers

---

## Database Issues

### Migrations failing
```bash
# Check migration status
php artisan migrate:status

# Reset and re-run (CAUTION: loses all data)
php artisan migrate:fresh --seed

# Run specific migration
php artisan migrate --path=database/migrations/filename.php
```

### Slow queries
- Check indexes: `SHOW INDEX FROM contacts;`
- The platform adds performance indexes in migration `2026_06_24_000003_add_enterprise_performance_indexes`
- Enable MySQL slow query log for diagnosis

---

## Frontend Issues

### Page shows blank / infinite spinner
1. Open browser DevTools → Console tab
2. Check for JavaScript errors
3. Check network tab for failed API calls (401 = not logged in, 403 = wrong role, 500 = server error)
4. Try clearing localStorage: `localStorage.clear()` in browser console, then reload

### "403 Forbidden" errors for staff
- This is expected for admin-only actions
- Verify the user's role in Users page
- Staff cannot: delete, import, export, access admin pages

### Changes not showing after build
```bash
cd frontend
npm run build
php artisan config:cache
```

---

## Queue Issues

### Failed jobs not being processed
```bash
php artisan queue:retry all
# or specific job
php artisan queue:retry {id}
```

### Queue worker memory leak
```bash
# Add memory limit to worker
php artisan queue:work --memory=256 --tries=3
```

---

## Performance Issues

### Contact list loading slowly
- Check if there are many contacts without indexes
- Run: `php artisan migrate` to ensure all indexes are applied
- Consider reducing per_page in System Settings

### Import taking too long
- Large imports (> 10,000 rows) run synchronously
- Consider splitting into smaller files (< 5,000 rows each)
- Ensure server has adequate memory (`memory_limit = 256M` in php.ini)

---

## Logs & Debugging

```bash
# View recent errors
tail -100 storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Enable debug mode temporarily (NEVER in production)
# Set APP_DEBUG=true in .env, then:
php artisan config:clear
```
