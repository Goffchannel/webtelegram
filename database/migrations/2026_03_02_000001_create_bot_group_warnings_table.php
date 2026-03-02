<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_group_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_group_id')->constrained('bot_groups')->cascadeOnDelete();
            $table->string('telegram_user_id');
            $table->string('telegram_username')->nullable();
            $table->unsignedTinyInteger('count')->default(1);
            $table->string('reason')->nullable();
            $table->timestamp('last_warned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['bot_group_id', 'telegram_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_group_warnings');
    }
};
