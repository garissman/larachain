<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Models\Agent;
use Structures\Enums\ChatStatuesEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Agent::class, 'agent_id')->nullable();
            $table->string('title')->nullable();
            $table->string('chat_driver')->nullable();
            $table->string('embedding_driver')->nullable();
            $table->string('session_id')->nullable();
            $table->string('chat_status')
                ->nullable()
                ->default(ChatStatuesEnum::NotStarted->value);
            $table->foreignIdFor(\App\Models\User::class, 'user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
