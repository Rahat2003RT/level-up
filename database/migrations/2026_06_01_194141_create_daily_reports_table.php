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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_cycle_id')->constrained('activity_cycles')->onDelete('cascade');

            $table->date('calendar_date');

            $table->integer('day_number');

            $table->string('status', 20)->default('completed');

            $table->integer('contacts_count')->default(0);       // Новые контакты
            $table->integer('presentations_count')->default(0);  // Проведенные презентации
            $table->decimal('sales_volume', 12, 2)->default(0.00); // Объем продаж (валюта/баллы)
            $table->integer('partners_count')->default(0);       // Новые партнеры в 1-ю линию
            $table->integer('team_meetings_count')->default(0);  // Встречи с командой (для Лидеров)
            $table->integer('self_education_minutes')->default(0); // Самообразование (в минутах)

            $table->timestamps();

            $table->unique(['activity_cycle_id', 'calendar_date']);
            $table->index(['activity_cycle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
