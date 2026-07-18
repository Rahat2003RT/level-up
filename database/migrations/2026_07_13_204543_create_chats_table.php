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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elite_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('leader_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['elite_id', 'leader_id']);
        });


        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Первичный ключ UUID
            $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');

            $table->text('text');

            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['chat_id', 'created_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('messages');
    }
};
