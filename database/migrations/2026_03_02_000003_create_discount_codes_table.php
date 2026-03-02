<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['percent', 'fixed']);
            $table->decimal('value', 8, 2);          // 10 → 10% | 5.00 → €5 off
            $table->decimal('min_amount', 8, 2)->nullable(); // minimum purchase amount
            $table->unsignedInteger('max_uses')->nullable(); // null = unlimited
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('discount_code', 50)->nullable()->after('amount');
            $table->decimal('discount_amount', 8, 2)->default(0)->after('discount_code');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['discount_code', 'discount_amount']);
        });
        Schema::dropIfExists('discount_codes');
    }
};
