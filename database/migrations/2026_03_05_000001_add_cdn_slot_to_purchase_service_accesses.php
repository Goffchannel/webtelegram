<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_service_accesses', function (Blueprint $table) {
            $table->unsignedTinyInteger('cdn_slot')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_service_accesses', function (Blueprint $table) {
            $table->dropColumn('cdn_slot');
        });
    }
};
