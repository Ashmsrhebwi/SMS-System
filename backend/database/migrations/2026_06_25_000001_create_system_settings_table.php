<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 50)->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->index('group');
        });

        DB::table('system_settings')->insert([
            // ── General ──────────────────────────────────────────────────────────
            ['key' => 'site_name',             'value' => 'FeRa Clinic SMS Platform', 'type' => 'string',  'group' => 'general',       'label' => 'Platform Name',              'description' => 'Display name for this SMS platform',                              'is_public' => true,  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'timezone',              'value' => 'UTC',                      'type' => 'string',  'group' => 'general',       'label' => 'Timezone',                   'description' => 'System timezone for displaying dates and scheduling campaigns',   'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_per_page',      'value' => '50',                       'type' => 'integer', 'group' => 'general',       'label' => 'Default Page Size',          'description' => 'Default number of records per page in lists',                     'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            // ── Twilio ───────────────────────────────────────────────────────────
            ['key' => 'twilio_rate_limit_ms',  'value' => '1000',                     'type' => 'integer', 'group' => 'twilio',        'label' => 'Send Rate Limit (ms)',       'description' => 'Milliseconds to wait between each SMS dispatch',                  'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'twilio_opt_out_text',   'value' => 'Reply STOP to unsubscribe','type' => 'string',  'group' => 'twilio',        'label' => 'Opt-Out Footer Text',        'description' => 'Text appended to messages for compliance opt-out instructions', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'twilio_enable_tracking','value' => 'true',                     'type' => 'boolean', 'group' => 'twilio',        'label' => 'Enable Link Tracking',       'description' => 'Automatically replace {tracking_url} placeholders in messages',   'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            // ── Campaigns ────────────────────────────────────────────────────────
            ['key' => 'campaign_batch_size',   'value' => '500',                      'type' => 'integer', 'group' => 'campaigns',     'label' => 'Batch Size',                 'description' => 'Number of contacts processed per dispatch chunk',                  'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'cost_per_segment',      'value' => '0.0079',                   'type' => 'string',  'group' => 'campaigns',     'label' => 'Cost Per SMS Segment',       'description' => 'Estimated cost per SMS segment in the configured currency',        'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency',              'value' => 'USD',                      'type' => 'string',  'group' => 'campaigns',     'label' => 'Currency Code',              'description' => 'ISO 4217 currency code for cost calculations',                     'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'currency_symbol',       'value' => '$',                        'type' => 'string',  'group' => 'campaigns',     'label' => 'Currency Symbol',            'description' => 'Symbol displayed next to cost figures',                           'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            // ── Security ─────────────────────────────────────────────────────────
            ['key' => 'session_timeout_min',   'value' => '480',                      'type' => 'integer', 'group' => 'security',      'label' => 'Session Timeout (min)',      'description' => 'Sanctum token expiry in minutes',                                 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_login_attempts',    'value' => '5',                        'type' => 'integer', 'group' => 'security',      'label' => 'Max Login Attempts',         'description' => 'Failed login attempts before temporary lockout',                   'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'otp_expiry_min',        'value' => '5',                        'type' => 'integer', 'group' => 'security',      'label' => 'OTP Expiry (min)',           'description' => 'How long an OTP code remains valid',                              'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            // ── Notifications ────────────────────────────────────────────────────
            ['key' => 'admin_alert_email',              'value' => '',  'type' => 'string',  'group' => 'notifications', 'label' => 'Admin Alert Email',             'description' => 'Additional email for system alerts (leave blank to use admin accounts)', 'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_failed_jobs',             'value' => 'true', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Alert on Failed Jobs',       'description' => 'Email admins when failed job count exceeds threshold',               'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'failed_jobs_threshold',          'value' => '5',  'type' => 'integer', 'group' => 'notifications', 'label' => 'Failed Jobs Threshold',        'description' => 'Number of failed jobs before sending an alert',                      'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_campaign_complete',       'value' => 'true', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Alert on Campaign Complete', 'description' => 'Email admins when a campaign finishes sending',                       'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_import_failure',          'value' => 'true', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Alert on Import Failure',    'description' => 'Email admins when a contact import has errors',                       'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_security_events',         'value' => 'true', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Alert on Security Events',   'description' => 'Email admins on suspicious login or access events',                  'is_public' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
