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

        // Замораживаем время на начало суток, чтобы избежать конфликтов с часовыми поясами
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 0, 0, 0));

        $this->player = User::factory()->create();
        $this->leader = User::factory()->create();

        Gate::define('access-leader', function (User $user) {
            return $user->id === $this->leader->id;
        });

        Gate::define('access-player', function (User $user) {
            return true;
        });
    }

    public function test_player_can_get_virtual_checklist_for_today(): void
    {
        $response = $this->actingAs($this->player)->getJson('/api/v1/plan/checklist');

        // Если здесь падает 500 из-за stdClass, убедитесь, что в DailyChecklistResource
        // вы используете $this->date, а не $this['date'].
        $response->assertOk()
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.date', '2024-01-15');
    }

    public function test_player_can_store_today_checklist(): void
    {
        $payload = [
            'scheduled_meetings' => 3,
            'completed_meetings' => 2,
            'new_clients' => 1,
            'social_media_activity' => true,
        ];

        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist', $payload);

        // Меняем 200 на 201 Created (успешное создание ресурса)
        $response->assertStatus(201)
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.completed_meetings', 2);

        $this->assertDatabaseHas('daily_checklists', [
            'user_id' => $this->player->id,
            'date' => '2024-01-15 00:00:00', // SQLite часто хранит дату с временем
            'is_completed' => true,
        ]);
    }

    public function test_player_cannot_store_checklist_twice_a_day(): void
    {
        // Создаем чек-лист на замороженную дату
        DailyChecklist::factory()->create([
            'user_id' => $this->player->id,
            'date' => Carbon::today()->toDateString(), // Строго по формату БД
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist', [
            'completed_meetings' => 5,
        ]);

        // Теперь сервис должен увидеть запись и бросить 403 AuthorizationException
        $response->assertStatus(403);
    }

    public function test_player_can_set_day_off(): void
    {
        $response = $this->actingAs($this->player)->postJson('/api/v1/plan/checklist/day-off');

        // Меняем 200 на 201 Created
        $response->assertStatus(201)
            ->assertJsonPath('data.is_day_off', true);

        $this->assertDatabaseHas('daily_checklists', [
            'user_id' => $this->player->id,
            'is_completed' => false,
            'is_day_off' => true,
        ]);
    }

    // ========================================================================= //
    //                               LEADER TESTS                                //
    // ========================================================================= //

    public function test_leader_can_get_virtual_checklist_for_today(): void
    {
        $response = $this->actingAs($this->leader)->getJson('/api/v1/plan/checklist');

        $response->assertOk()
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.date', '2024-01-15');
    }

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

        // Меняем 200 на 201 Created
        $response->assertStatus(201)
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.checked_team_activity', true);
    }

    public function test_leader_can_set_day_off(): void
    {
        $response = $this->actingAs($this->leader)->postJson('/api/v1/plan/checklist/day-off');

        // Меняем 200 на 201 Created
        $response->assertStatus(201)
            ->assertJsonPath('data.is_day_off', true);
    }
}
