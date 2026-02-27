<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_service_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->unique()->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('service_access_line_id')->nullable()->constrained('service_access_lines')->nullOnDelete();
            $table->string('access_token', 120)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_service_accesses');
    }
};
