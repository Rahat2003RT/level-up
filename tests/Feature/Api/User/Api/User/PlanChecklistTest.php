<?php

namespace Tests\Feature\Api\User;

use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PlanChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected User $player;
    protected User $leader;

    protected function setUp(): void
    {
        parent::setUp();

        // Замораживаем время, чтобы тесты не падали, если они запустятся в 23:59:59
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 12, 0, 0));

        $this->player = User::factory()->create();
        $this->leader = User::factory()->create();

        // Имитируем проверку прав (Gate) для наших тестовых пользователей.
        // В реальном приложении это делает Spatie Permission или ваши кастомные Gate.
        Gate::define('access-leader', function (User $user) {
            return $user->id === $this->leader->id;
        });
    }

    // ========================================================================= //
    //                               PLAYER TESTS                                //
    // ========================================================================= //

    /**
     * Игрок может получить пустой (виртуальный) чек-лист на сегодня.
     */
    public function test_player_can_get_virtual_checklist_for_today(): void
    {
        $response = $this->actingAs($this->player)->getJson('/api/v1/plan/checklist');

        $response->assertOk()
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.is_day_off', false)
            ->assertJsonPath('data.scheduled_meetings', 0)
            ->assertJsonPath('data.date', '2024-01-15');
    }

    /**
     * Игрок может успешно заполнить чек-лист на сегодня.
     */
    public function test_player_can_store_today_checklist(): void
    {
        $payload = [
            'scheduled_meetings' => 3,
            'completed_meetings' => 2,
            'new_clients' => 1,
            'social_media_activity' => true,
            'notes_for_the_day' => 'Отличный день!'
        ];

        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist', $payload);

        $response->assertStatus(200) // или 201 в зависимости от реализации Resource
        ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.completed_meetings', 2);

        $this->assertDatabaseHas('daily_checklists', [
            'user_id' => $this->player->id,
            'date' => '2024-01-15',
            'is_completed' => true,
            'is_day_off' => false,
            'completed_meetings' => 2,
        ]);
    }

    /**
     * Игрок НЕ может заполнить чек-лист дважды за один день.
     */
    public function test_player_cannot_store_checklist_twice_a_day(): void
    {
        // Создаем заполненный чек-лист на сегодня
        DailyChecklist::factory()->create([
            'user_id' => $this->player->id,
            'date' => '2024-01-15',
            'is_completed' => true,
        ]);

        // Пытаемся отправить данные снова
        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist', [
            'completed_meetings' => 5,
        ]);

        // Ожидаем ошибку 403 (AuthorizationException) из сервиса
        $response->assertStatus(403);
    }

    /**
     * Игрок может взять выходной.
     */
    public function test_player_can_set_day_off(): void
    {
        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist/day-off');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_day_off', true);

        $this->assertDatabaseHas('daily_checklists', [
            'user_id' => $this->player->id,
            'date' => '2024-01-15',
            'is_completed' => false,
            'is_day_off' => true,
        ]);
    }

    // ========================================================================= //
    //                               LEADER TESTS                                //
    // ========================================================================= //

    /**
     * Лидер получает свою (лидерскую) структуру виртуального чек-листа.
     */
    public function test_leader_can_get_virtual_checklist_for_today(): void
    {
        $response = $this->actingAs($this->leader)->getJson('/api/v1/plan/checklist');

        $response->assertOk()
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.checked_team_activity', false) // Убеждаемся, что поля лидерские
            ->assertJsonPath('data.date', '2024-01-15');
    }

    /**
     * Лидер может успешно заполнить свой чек-лист.
     */
    public function test_leader_can_store_today_checklist(): void
    {
        $payload = [
            'checked_team_activity' => true,
            'contacted_players' => true,
            'added_new_player' => false,
            'held_online_meeting' => true,
            'posted_engaged_social_media' => false,
            'attracted_new_client' => true,
            'brought_new_partner' => false,
            'sent_new_invitations' => true,
        ];

        $response = $this->actingAs($this->leader)->postJson('/api/v1/plan/checklist', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.checked_team_activity', true);

        $this->assertDatabaseHas('leadership_checklists', [
            'user_id' => $this->leader->id,
            'date' => '2024-01-15',
            'is_completed' => true,
            'checked_team_activity' => true,
        ]);
    }

    /**
     * Ошибка валидации: Лидер не передал обязательные поля.
     */
    public function test_leader_validation_fails_on_missing_fields(): void
    {
        // Для лидера все булевы поля обязательны. Отправляем пустой массив.
        $response = $this->actingAs($this->leader)->postJson('/api/v1/plan/checklist', []);

        // Ожидаем 422 Unprocessable Entity
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'checked_team_activity',
                'contacted_players',
                'added_new_player'
            ]);
    }

    /**
     * Лидер может взять выходной.
     */
    public function test_leader_can_set_day_off(): void
    {
        $response = $this->actingAs($this->leader)->postJson('/api/v1/plan/checklist/day-off');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_day_off', true);

        $this->assertDatabaseHas('leadership_checklists', [
            'user_id' => $this->leader->id,
            'date' => '2024-01-15',
            'is_completed' => false,
            'is_day_off' => true,
        ]);
    }
}
