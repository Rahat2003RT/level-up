<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->unsignedInteger('day_number');

            // Флаги состояния дня
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_day_off')->default(false);

            // 8 булевых показателей со скриншота
            $table->boolean('checked_team_activity')->default(false);
            $table->boolean('contacted_players')->default(false);
            $table->boolean('added_new_player')->default(false);
            $table->boolean('held_online_meeting')->default(false);
            $table->boolean('posted_engaged_social_media')->default(false);
            $table->boolean('attracted_new_client')->default(false);
            $table->boolean('brought_new_partner')->default(false);
            $table->boolean('sent_new_invitations')->default(false);

            // Текстовый блок (на случай, если кнопка Finish the day тоже открывает заметки)
            $table->text('notes_for_the_day')->nullable();

            $table->timestamps();

            // Уникальный индекс, чтобы у юзера был только один чек-лист лидера за день
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_checklists');
    }
};
