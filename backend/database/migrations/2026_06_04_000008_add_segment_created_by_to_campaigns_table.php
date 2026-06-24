<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('segment_id')->nullable()->constrained()->nullOnDelete()->after('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('segment_id');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['segment_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['segment_id', 'created_by']);
        });
    }
};
