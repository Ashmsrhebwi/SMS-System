<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Composite index for campaign statistics queries
            $table->index(['campaign_id', 'status'], 'messages_campaign_status_idx');
            // Index for standalone status lookups (queue processing, reports)
            $table->index('status', 'messages_status_idx');
            // Make twilio_sid unique to prevent duplicate webhook processing
            $table->unique('twilio_sid', 'messages_twilio_sid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_campaign_status_idx');
            $table->dropIndex('messages_status_idx');
            $table->dropUnique('messages_twilio_sid_unique');
        });
    }
};
