<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignIdFor(\App\Models\User::class, 'user_id')->nullable();
            $table->string('role')->default('user');
            $table->text('body');
            $table->json('meta_data')->nullable();
            $table->json('tools')->nullable();
            $table->string('tool_name')->nullable();
            $table->string('tool_id')->nullable();
            $table->string('driver')->nullable();
            $table->boolean('is_been_whisper')->default(0);
            $table->boolean('in_out')->default(0);
            $table->boolean('is_chat_ignored')->default(0);
            $table->string('session_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
