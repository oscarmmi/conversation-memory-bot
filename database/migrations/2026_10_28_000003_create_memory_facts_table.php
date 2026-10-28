<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memory_facts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
            $table->string('key', 40);
            $table->text('value');
            $table->foreignId('source_turn_id')->nullable()->constrained('chat_turns')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memory_facts');
    }
};
