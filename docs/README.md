# FeRa Clinic SMS Platform — Documentation

> Enterprise SMS Marketing Platform built on Laravel 12 + React 18

---

## Table of Contents

1. [Installation Guide](./installation.md)
2. [Deployment Guide](./deployment.md)
3. [Configuration Guide](./configuration.md)
4. [Twilio Setup](./twilio.md)
5. [Campaign Flow](./campaign-flow.md)
6. [Segmentation Guide](./segmentation.md)
7. [Queue & Workers](./queues.md)
8. [Backup & Restore](./backup.md)
9. [Database Schema](./database.md)
10. [Troubleshooting Guide](./troubleshooting.md)
11. [Maintenance Guide](./maintenance.md)
12. [Production Checklist](./production-checklist.md)
13. [Disaster Recovery](./disaster-recovery.md)

---

## Architecture Overview

```
Browser (React SPA)
       │
       ▼
Laravel API (api/v1/*)
       │
       ├── Sanctum Token Auth + 2FA OTP
       ├── Queue (Jobs table → SendSmsJob)
       ├── Twilio REST API
       └── MySQL Database
```

### Technology Stack

| Layer        | Technology      | Version   |
|-------------|----------------|-----------|
| Backend      | Laravel         | 12.x      |
| Frontend     | React + Vite    | 18/19     |
| Database     | MySQL           | 8.0+      |
| Auth         | Sanctum + OTP   | —         |
| SMS          | Twilio          | —         |
| Queue        | Database/Redis  | —         |
| CSS          | Tailwind CSS    | 3.x       |
| Deployment   | Any PHP 8.2+    | —         |

---

## Quick Start

```bash
git clone <repo>
cd SMS-System

# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed    # optional demo data

# Frontend
cd ../frontend
npm install
npm run build

# Start queue worker
php artisan queue:work --tries=3 --timeout=60

# Visit
http://your-domain/
```
