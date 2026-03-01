<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_service_accesses', function (Blueprint $table) {
            // JSON array of IPs that have used this token
            $table->text('bound_ips')->nullable()->after('last_viewed_at');
            // Max allowed unique IPs (default 1, admin can raise it)
            $table->unsignedTinyInteger('max_ips')->default(1)->after('bound_ips');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_service_accesses', function (Blueprint $table) {
            $table->dropColumn(['bound_ips', 'max_ips']);
        });
    }
};
