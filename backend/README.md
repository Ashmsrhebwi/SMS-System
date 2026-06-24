# FeRa Clinic — SMS Campaign System

نظام إدارة حملات SMS داخلي لعيادة أسنان FeRa Clinic، مبني بـ Laravel 11 + Twilio.

---

## المتطلبات

- PHP 8.2+
- Composer
- MySQL 8+ (أو SQLite للتطوير)
- Node.js + npm
- حساب Twilio مع Messaging Service + Alphanumeric Sender ID مفعّل للـ UK

---

## خطوات التثبيت

### 1. تثبيت التبعيات

```bash
composer install
npm install && npm run build
```

### 2. إعداد البيئة

```bash
cp .env.example .env
php artisan key:generate
```

عدّل `.env` وأضف:

```env
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fera_sms
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_CONNECTION=database

TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_MESSAGING_SERVICE_SID=MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

SMS_RATE_LIMIT_DELAY=1
SMS_OPT_OUT_TEXT="To stop receiving messages, visit: {opt_out_url}"
SMS_WHATSAPP_URL=https://wa.me/447XXXXXXXXX
```

### 3. قاعدة البيانات

```bash
php artisan migrate
```

### 4. إنشاء مستخدم للوحة التحكم

```bash
php artisan tinker
# ثم:
\App\Models\User::factory()->create(['name' => 'Admin', 'email' => 'admin@fera.clinic', 'password' => bcrypt('yourpassword')]);
```

### 5. تشغيل Queue Worker

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=60
```

أو في الإنتاج، استخدم **Supervisor**:

```ini
[program:fera-sms-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
numprocs=1
```

---

## إعداد Twilio Webhook

في لوحة تحكم Twilio > Messaging Service > اضبط:

- **Status Callback URL:** `https://yourdomain.com/webhooks/twilio/status`

تأكد أن هذا الـ endpoint عام وقابل للوصول من Twilio.

---

## هيكل النظام

| المسار | الوصف |
|--------|-------|
| `/dashboard` | لوحة الإحصائيات الرئيسية |
| `/campaigns` | قائمة الحملات |
| `/campaigns/create` | إنشاء حملة جديدة |
| `/campaigns/{id}` | تقرير الحملة التفصيلي |
| `/contacts` | قائمة جهات الاتصال |
| `/contacts/import` | استيراد من Excel/CSV |
| `/r/{token}` | تتبّع النقرات (عام) |
| `/optout` | إلغاء الاشتراك (عام) |
| `/webhooks/twilio/status` | Webhook لتحديثات Twilio (عام) |

---

## تنسيق ملف Excel/CSV

| العمود | الوصف | إلزامي |
|--------|-------|--------|
| `name` أو `full_name` | اسم جهة الاتصال | ✓ |
| `phone` أو `mobile` | رقم الهاتف | ✓ |
| `last_visit` | تاريخ آخر زيارة | |
| `notes` | ملاحظات | |

**صيغ الأرقام المقبولة (UK):**
- `07XXX XXXXXX` ← يتحول لـ `+447XXXXXXXXX`
- `+447XXXXXXXXX` ← صحيح مباشرة
- `447XXXXXXXXX` ← يتحول لـ `+447XXXXXXXXX`

---

## متغيّرات الرسائل

داخل نص الرسالة يمكن استخدام:

| المتغيّر | يُستبدل بـ |
|---------|-----------|
| `{name}` | اسم جهة الاتصال |
| `{tracking_url}` | رابط تتبّع النقرات الخاص بكل رسالة |

---

## الامتثال القانوني (UK GDPR / PECR)

- النظام يرسل فقط لجهات الاتصال ذات `opted_in = true`
- المُلغون (`opt_outs`) يُستثنون تلقائياً من كل الحملات
- كل رسالة تتضمن رابط إلغاء اشتراك (قابل للتعديل من `.env`)
- لا تُرسل إلا للزبائن الذين وافقوا مسبقاً

---

## تعديل معدّل الإرسال

```env
SMS_RATE_LIMIT_DELAY=2  # ثانيتان بين كل رسالة
```

ابدأ بـ 1-2 ثانية وزِد تدريجياً مع نمو سمعة المُرسِل.

---

## اختبار قبل الإطلاق

1. أضف رقمك البريطاني كجهة اتصال واحدة.
2. أنشئ حملة واضغط "Send Now".
3. تحقق من:
   - وصول الرسالة باسم "FeRa Clinic"
   - تحديث الحالة في لوحة التحكم (يحتاج Webhook مفعّل)
   - عمل رابط التتبّع والـ redirect

---

## حدود Twilio للحسابات الجديدة

- الحسابات الجديدة تبدأ بحدود يومية منخفضة (~100/يوم).
- ابدأ بـ 50-100 رسالة يومياً لبناء سمعة المُرسِل.
- تواصل مع Twilio Support لرفع الحدود بعد الاختبار الأولي.
