<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comprehensive schema hardening migration:
 *
 * 1. messages.contact_id  — nullable + nullOnDelete (prevents data loss on contact force-delete)
 * 2. contact_notes.user_id — nullable + nullOnDelete (preserves notes when staff member deleted)
 * 3. suppression_list      — add proper FK constraints for contact_id and added_by
 * 4. campaigns             — add index on status and scheduled_at
 * 5. audit_logs            — add standalone created_at index for time-range queries
 * 6. security_logs         — add indexes on created_at and event
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. messages.contact_id: nullable + nullOnDelete ────────────────────
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('contacts')
                  ->nullOnDelete();
        });

        // ── 2. contact_notes.user_id: nullable + nullOnDelete ──────────────────
        Schema::table('contact_notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('contact_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // ── 3. suppression_list: add FK constraints ────────────────────────────
        Schema::table('suppression_list', function (Blueprint $table) {
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('contacts')
                  ->nullOnDelete();

            $table->foreign('added_by')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });

        // ── 4. campaigns: missing indexes ─────────────────────────────────────
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index('status',       'campaigns_status_idx');
            $table->index('scheduled_at', 'campaigns_scheduled_at_idx');
        });

        // ── 5. audit_logs: standalone created_at index ────────────────────────
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'audit_logs_created_at_idx');
            $table->index('entity_type', 'audit_logs_entity_type_idx');
        });

        // ── 6. security_logs: created_at + event indexes ──────────────────────
        Schema::table('security_logs', function (Blueprint $table) {
            $table->index('created_at', 'security_logs_created_at_idx');
            $table->index('event',      'security_logs_event_idx');
        });
    }

    public function down(): void
    {
        // Revert security_logs indexes
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropIndex('security_logs_created_at_idx');
            $table->dropIndex('security_logs_event_idx');
        });

        // Revert audit_logs indexes
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_created_at_idx');
            $table->dropIndex('audit_logs_entity_type_idx');
        });

        // Revert campaigns indexes
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_status_idx');
            $table->dropIndex('campaigns_scheduled_at_idx');
        });

        // Revert suppression_list FKs
        Schema::table('suppression_list', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['added_by']);
        });

        // Revert contact_notes.user_id to NOT NULL + cascadeOnDelete
        Schema::table('contact_notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('contact_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Revert messages.contact_id to NOT NULL + cascadeOnDelete
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable(false)->change();
            $table->foreign('contact_id')->references('id')->on('contacts')->cascadeOnDelete();
        });
    }
};
