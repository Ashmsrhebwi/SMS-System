<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('sms_segments')->default(1)->after('message_body');
            $table->decimal('cost', 8, 4)->default(0.0000)->after('sms_segments');
            $table->unsignedTinyInteger('resend_count')->default(0)->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sms_segments', 'cost', 'resend_count']);
        });
    }
};
