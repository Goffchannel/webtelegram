<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_group_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_group_id')->constrained('bot_groups')->cascadeOnDelete();
            $table->string('trigger'); // e.g. "!lista", "/precios", "ayuda"
            $table->text('response');  // Markdown response text
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['bot_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_group_commands');
    }
};
