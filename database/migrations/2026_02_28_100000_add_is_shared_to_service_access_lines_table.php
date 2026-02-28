<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_access_lines', function (Blueprint $table) {
            // When true, this line is a "template" shared by all subscribers of the product.
            // The ServiceAccessManager will NOT mark it as is_assigned = true,
            // allowing multiple PurchaseServiceAccess records to reference it.
            $table->boolean('is_shared')->default(false)->after('is_assigned');
        });
    }

    public function down(): void
    {
        Schema::table('service_access_lines', function (Blueprint $table) {
            $table->dropColumn('is_shared');
        });
    }
};
