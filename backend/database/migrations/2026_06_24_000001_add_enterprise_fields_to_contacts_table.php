<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('country', 100)->nullable()->after('email');
            $table->string('language', 50)->nullable()->after('country');
            $table->enum('status', ['active', 'inactive', 'interested', 'follow_up', 'not_interested'])
                  ->default('active')->after('language');
            $table->string('source', 50)->default('manual')->after('status');

            // Performance indexes for new columns
            $table->index('country', 'contacts_country_idx');
            $table->index('language', 'contacts_language_idx');
            $table->index('status', 'contacts_status_idx');
            $table->index('source', 'contacts_source_idx');
            // Composite index for common filtering patterns
            $table->index(['opted_in', 'country'], 'contacts_opted_in_country_idx');
            $table->index(['opted_in', 'status'], 'contacts_opted_in_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('contacts_opted_in_status_idx');
            $table->dropIndex('contacts_opted_in_country_idx');
            $table->dropIndex('contacts_source_idx');
            $table->dropIndex('contacts_status_idx');
            $table->dropIndex('contacts_language_idx');
            $table->dropIndex('contacts_country_idx');
            $table->dropColumn(['country', 'language', 'status', 'source']);
        });
    }
};
