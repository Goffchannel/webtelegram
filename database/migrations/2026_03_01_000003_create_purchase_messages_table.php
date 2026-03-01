<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['admin', 'user']);
            $table->string('sender_name');                    // Admin name or @username
            $table->text('message');
            $table->bigInteger('telegram_message_id')->nullable(); // Telegram's message_id for reply tracking
            $table->timestamp('read_at')->nullable();         // Null = unread (only relevant for user messages)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_messages');
    }
};
