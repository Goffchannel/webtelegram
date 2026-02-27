<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_access_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('line_name');
            $table->text('m3u_url');
            $table->string('line_username')->nullable();
            $table->string('line_password')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_assigned')->default(false)->index();
            $table->foreignId('assigned_purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->index(['video_id', 'is_assigned']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_access_lines');
    }
};
