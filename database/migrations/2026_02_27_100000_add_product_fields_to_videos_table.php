<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (!Schema::hasColumn('videos', 'product_type')) {
                $table->string('product_type', 30)->default('video')->index();
            }
            if (!Schema::hasColumn('videos', 'long_description')) {
                $table->text('long_description')->nullable();
            }
            if (!Schema::hasColumn('videos', 'fan_message')) {
                $table->text('fan_message')->nullable();
            }
            if (!Schema::hasColumn('videos', 'access_instructions')) {
                $table->text('access_instructions')->nullable();
            }
            if (!Schema::hasColumn('videos', 'duration_days')) {
                $table->unsignedInteger('duration_days')->default(30);
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $columns = ['product_type', 'long_description', 'fan_message', 'access_instructions', 'duration_days'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('videos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
