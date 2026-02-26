<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_creator')) {
                $table->boolean('is_creator')->default(false)->index();
            }

            if (!Schema::hasColumn('users', 'creator_slug')) {
                $table->string('creator_slug')->nullable()->unique();
            }

            if (!Schema::hasColumn('users', 'creator_store_name')) {
                $table->string('creator_store_name')->nullable();
            }

            if (!Schema::hasColumn('users', 'creator_bio')) {
                $table->text('creator_bio')->nullable();
            }

            if (!Schema::hasColumn('users', 'creator_subscription_status')) {
                $table->string('creator_subscription_status')->default('inactive')->index();
            }

            if (!Schema::hasColumn('users', 'creator_subscription_ends_at')) {
                $table->timestamp('creator_subscription_ends_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'creator_payment_methods')) {
                $table->json('creator_payment_methods')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'is_creator',
                'creator_slug',
                'creator_store_name',
                'creator_bio',
                'creator_subscription_status',
                'creator_subscription_ends_at',
                'creator_payment_methods',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
