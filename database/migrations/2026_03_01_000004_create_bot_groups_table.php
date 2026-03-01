<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_groups', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unique(); // Telegram group IDs are negative bigints
            $table->string('chat_title');
            $table->enum('chat_type', ['group', 'supergroup', 'channel'])->default('group');
            $table->string('username')->nullable(); // @groupname if public
            $table->boolean('is_active')->default(true)->index();
            $table->integer('member_count')->nullable();
            $table->json('settings')->nullable(); // auto_delete_links, delete_link_action, welcome_enabled, welcome_message
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_groups');
    }
};
