<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            $table->string('recurrence')->nullable()->after('trigger'); // daily|weekly|monthly|null
            $table->string('recurrence_time', 5)->nullable()->after('recurrence'); // HH:mm
            $table->tinyInteger('recurrence_day')->nullable()->after('recurrence_time'); // 0-6 weekly, 1-31 monthly
            $table->string('recurrence_timezone')->default('Europe/Madrid')->after('recurrence_day');
        });
    }

    public function down(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'recurrence_time', 'recurrence_day', 'recurrence_timezone']);
        });
    }
};
