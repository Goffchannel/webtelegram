<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            // Trigger command: when a user types this in any group, the bot sends the media
            $table->string('trigger')->nullable()->unique()->after('caption');
        });

        Schema::table('bot_broadcast_targets', function (Blueprint $table) {
            // Per-group scheduled send time
            $table->timestamp('scheduled_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bot_broadcasts', function (Blueprint $table) {
            $table->dropColumn('trigger');
        });
        Schema::table('bot_broadcast_targets', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });
    }
};
