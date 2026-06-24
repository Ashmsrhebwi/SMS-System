<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            // session_token is looked up directly during OTP verification
            // and is generated with Str::random(64) so uniqueness is guaranteed
            $table->unique('session_token');
        });
    }

    public function down(): void
    {
        Schema::table('login_otps', function (Blueprint $table) {
            $table->dropUnique(['session_token']);
        });
    }
};
