# Campaign Flow

Complete lifecycle of an SMS campaign from creation to delivery.

---

## 1. Campaign States

```
draft → scheduled → sending → completed
                 ↘ cancelled
```

| State | Description |
|-------|------------|
| `draft` | Created but not sent or scheduled |
| `scheduled` | Scheduled for a future time (not yet dispatched) |
| `sending` | DispatchCampaignJob is running; messages are being sent |
| `completed` | All messages dispatched (delivered or failed) |
| `cancelled` | Campaign was cancelled before completion |

---

## 2. Creation Flow

1. **Admin** creates campaign via Campaign Wizard
2. Sets: name, message body, target segment (or all opted-in contacts)
3. Optionally uses template variables: `{name}`, `{first_name}`, `{last_name}`, `{tracking_url}`, `{date}`
4. Optionally includes `{tracking_url}` for WhatsApp/click tracking
5. Chooses: **Send Now** or **Schedule for later**

---

## 3. Dispatch Flow (Background Job)

When sent, `DispatchCampaignJob` runs in the background:

```php
DispatchCampaignJob::dispatch($campaign)
    ├── Sets campaign status = 'sending'
    ├── Counts eligible contacts via SegmentService
    │     (excludes opted-out, blacklisted, suppressed)
    ├── Updates campaign.total_recipients
    └── Chunks contacts (500 at a time)
          └── For each contact:
                ├── Creates Message record (status = 'pending')
                ├── Personalizes message body (replaces {name}, etc.)
                ├── If {tracking_url}: creates Click record with unique token
                │     Replaces {tracking_url} with /r/{token} URL
                ├── Logs: "Added to campaign" in ContactActivity
                └── Dispatches SendSmsJob with staggered delay
```

---

## 4. Send Flow (Per-Message Job)

```php
SendSmsJob::handle()
    ├── Re-validates: opted-in? blacklisted? suppressed?
    ├── If any fail: marks Message as 'failed' → returns
    └── TwilioService::sendMessage($phone, $body, $callbackUrl)
          ├── Success: updates Message with twilio_sid, status='queued', cost
          └── Failure: updates Message with status='failed', error_code/message
```

---

## 5. Webhook Flow (Twilio → Platform)

Twilio calls `POST /webhooks/twilio/status` when message status changes:

```
Twilio → POST /webhooks/twilio/status
    ├── Validates Twilio signature
    ├── Finds Message by twilio_sid
    ├── Updates: status, delivered_at, error_code
    ├── If delivered: ActivityLogger::smsDelivered()
    ├── If failed/undelivered: ActivityLogger::smsFailed()
    │     + Auto-suppresses after threshold failures
    └── CampaignCompletionService::checkAndComplete()
          → If no more pending/queued/sent: campaign.status = 'completed'
          → Sends admin notification email
```

---

## 6. Click Tracking Flow

When a contact clicks the tracking link:

```
Contact clicks /r/{token}
    └── TrackingController::redirect()
          ├── Validates target URL (SSRF protection)
          ├── Updates Click.click_count++, first_clicked_at
          ├── ActivityLogger::linkClicked()
          └── Redirects to target_url
```

---

## 7. Resend Failed

Admin can resend failed messages:

```php
CampaignController::resendFailed()
    └── Finds messages with status='failed'|'undelivered'
    └── Dispatches SendSmsJob for each (re-validates compliance)
```

---

## 8. Message Personalisation Variables

| Variable | Replacement |
|----------|------------|
| `{name}` | Contact full name |
| `{first_name}` | First word of contact name |
| `{last_name}` | Last word of contact name |
| `{clinic_name}` | "FeRa Clinic" |
| `{date}` | Today's date (d/m/Y) |
| `{tracking_url}` | Generated click tracking URL |

---

## 9. Compliance Checks

Before sending each SMS, the system verifies:

- Contact `opted_in = true`
- Phone NOT in `opt_outs` table
- Phone NOT in `global_blacklist` table
- Phone NOT in `suppression_list` table
- Contact not soft-deleted

Any failed check → message marked `failed` with reason logged.
