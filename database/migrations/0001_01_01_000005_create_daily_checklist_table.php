<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->unsignedInteger('day_number');

            // Флаги состояния дня
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_day_off')->default(false);

            // Числовые показатели
            $table->unsignedInteger('scheduled_meetings')->default(0);
            $table->unsignedInteger('completed_meetings')->default(0);
            $table->unsignedInteger('new_clients')->default(0);
            $table->unsignedInteger('new_partners')->default(0);
            $table->unsignedInteger('business_conversations')->default(0);
            $table->unsignedInteger('presentations')->default(0);
            $table->unsignedInteger('sales')->default(0);
            $table->unsignedBigInteger('daily_income')->default(0);

            // Булевы переключатели (Да/Нет)
            $table->boolean('social_media_activity')->default(false);
            $table->boolean('communication_with_sponsor')->default(false);

            // Текстовые блоки
            $table->text('notes_for_the_day')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_checklists');
    }
};
