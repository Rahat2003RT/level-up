<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->unsignedInteger('daily_calls')->default(0);
            $table->unsignedInteger('daily_meetings')->default(0);
            $table->unsignedInteger('business_conversations')->default(0);
            $table->unsignedInteger('presentations')->default(0);
            $table->unsignedInteger('social_media_posts')->default(0);
            $table->unsignedInteger('new_clients_per_week')->default(0);
            $table->unsignedInteger('new_partners_per_week')->default(0);
            $table->unsignedInteger('daily_volume_points')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_plans');
    }
};
