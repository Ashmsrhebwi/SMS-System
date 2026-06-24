<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contacts: additional high-value indexes for scale
        Schema::table('contacts', function (Blueprint $table) {
            // Full-text search index
            $table->index('name', 'contacts_name_idx');
            $table->index('email', 'contacts_email_idx');
            // Sorting & pagination performance
            $table->index(['created_at', 'id'], 'contacts_created_id_cursor_idx');
        });

        // Messages: enrich reporting indexes
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['created_at', 'status'], 'messages_created_status_idx');
            $table->index(['campaign_id', 'contact_id'], 'messages_campaign_contact_idx');
            $table->index('contact_id', 'messages_contact_idx');
            $table->index('cost', 'messages_cost_idx');
        });

        // Clicks: tracking index
        Schema::table('clicks', function (Blueprint $table) {
            $table->index('click_count', 'clicks_count_idx');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_created_id_cursor_idx');
            $table->dropIndex('contacts_email_idx');
            $table->dropIndex('contacts_name_idx');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_cost_idx');
            $table->dropIndex('messages_contact_idx');
            $table->dropIndex('messages_campaign_contact_idx');
            $table->dropIndex('messages_created_status_idx');
        });

        Schema::table('clicks', function (Blueprint $table) {
            $table->dropIndex('clicks_count_idx');
        });
    }
};
