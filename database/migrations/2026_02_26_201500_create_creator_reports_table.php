<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_telegram')->nullable();
            $table->string('reason', 120);
            $table->text('message');
            $table->string('status', 20)->default('open')->index(); // open|reviewing|resolved
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['creator_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_reports');
    }
};
