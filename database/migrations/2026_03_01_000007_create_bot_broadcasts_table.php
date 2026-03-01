<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_file_id');
            $table->string('file_type')->default('video'); // video, photo, document, animation
            $table->string('caption')->nullable();
            $table->enum('status', ['pending', 'sending', 'done', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bot_broadcast_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_broadcast_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bot_group_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['bot_broadcast_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_broadcast_targets');
        Schema::dropIfExists('bot_broadcasts');
    }
};
