<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'creator_id')) {
                $table->unsignedBigInteger('creator_id')->nullable()->index();
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('purchases', 'payment_method')) {
                $table->string('payment_method')->default('stripe')->index();
            }

            if (!Schema::hasColumn('purchases', 'payment_instructions')) {
                $table->text('payment_instructions')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }

            if (!Schema::hasColumn('purchases', 'proof_url')) {
                $table->string('proof_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'creator_id')) {
                $table->dropForeign(['creator_id']);
            }

            $columns = [
                'creator_id',
                'payment_method',
                'payment_instructions',
                'payment_reference',
                'proof_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('purchases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
