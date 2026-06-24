<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppression_list', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('name')->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('error_code', 20)->nullable();
            $table->unsignedInteger('failure_count')->default(1);
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('added_by')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();

            $table->index('phone', 'suppression_phone_idx');
            $table->index('contact_id', 'suppression_contact_idx');
            $table->index('created_at', 'suppression_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppression_list');
    }
};
