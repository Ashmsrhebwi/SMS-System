# Backup & Restore Guide

---

## What to Back Up

| Item | Frequency | Method |
|------|-----------|--------|
| MySQL database | Daily (minimum) | `mysqldump` |
| `.env` file | On every change | Secure offsite copy |
| `storage/` directory | Daily | `rsync` or S3 sync |
| System settings | Covered by DB backup | — |

---

## Database Backup

### Manual Backup

```bash
# Full backup
mysqldump -u sms_user -p sms_platform \
  --single-transaction \
  --routines \
  --triggers \
  > backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Compressed backup
mysqldump -u sms_user -p sms_platform \
  --single-transaction \
  | gzip > /backups/sms_$(date +%Y%m%d).sql.gz
```

### Automated Daily Cron

```bash
# Add to /etc/cron.d/sms-backup
0 2 * * * root mysqldump -u sms_user -pPASSWORD sms_platform --single-transaction | gzip > /backups/sms_$(date +\%Y\%m\%d).sql.gz
```

### Retention Policy

```bash
# Delete backups older than 30 days
find /backups/ -name "sms_*.sql.gz" -mtime +30 -delete
```

---

## Restore from Backup

```bash
# Stop queue worker first
sudo supervisorctl stop sms-queue:*

# Restore database
gunzip -c /backups/sms_20260625.sql.gz | mysql -u sms_user -p sms_platform

# Clear application caches
cd /var/www/sms-platform/backend
php artisan cache:clear
php artisan config:cache

# Restart worker
sudo supervisorctl start sms-queue:*
```

---

## Off-site Backup (S3)

```bash
# Install AWS CLI
apt install awscli

# Configure
aws configure

# Upload backup
aws s3 cp /backups/sms_$(date +%Y%m%d).sql.gz s3://your-bucket/sms-backups/

# Automated with cron
0 3 * * * aws s3 sync /backups/ s3://your-bucket/sms-backups/ --delete
```

---

## Verifying Backups

```bash
# Test backup integrity
gunzip -c /backups/sms_20260625.sql.gz | mysql -u sms_user -p sms_test_restore

# Check table counts
mysql -u sms_user -p sms_test_restore -e "
SELECT 'contacts', COUNT(*) FROM contacts
UNION SELECT 'messages', COUNT(*) FROM messages
UNION SELECT 'campaigns', COUNT(*) FROM campaigns;
"
```

Always verify backups can be restored in a test environment before relying on them.

---

## Disaster Recovery Time Objectives

| Scenario | Recovery Time | Data Loss |
|----------|--------------|-----------|
| Server crash (data intact) | < 30 min | Zero |
| Full server loss (with backups) | 2-4 hours | Up to 24 hours |
| Database corruption (with backups) | 1-2 hours | Up to 24 hours |
