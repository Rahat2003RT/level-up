<?php

namespace Tests\Feature\Api\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PlanStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $player;
    protected User $leader;
    protected User $elite;

    protected function setUp(): void
    {
        parent::setUp();

        // Замораживаем время для точности расчетов (например, 15 января)
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 0, 0, 0));

        $this->elite = User::factory()->create();

        $this->leader = User::factory()->create([
            'leader_id' => $this->elite->id
        ]);

        $this->player = User::factory()->create([
            'leader_id' => $this->leader->id
        ]);

        // Настраиваем роли (замените на вашу реальную логику ролей, если она отличается)
        Gate::define('access-player', fn (User $user) => true);

        Gate::define('access-leader', function (User $user) {
            return in_array($user->id, [$this->leader->id, $this->elite->id]);
        });

        Gate::define('access-elite', function (User $user) {
            return $user->id === $this->elite->id;
        });
    }

    public function test_player_can_see_only_personal_statistics(): void
    {
        // Создаем данные для игрока
        DailyChecklist::factory()->create([
            'user_id' => $this->player->id,
            'date' => Carbon::today()->toDateString(),
            'is_completed' => true,
            'completed_meetings' => 5,
            'daily_income' => 100.50,
        ]);

        Contact::factory()->create([
            'user_id' => $this->player->id,
            'volume' => 500,
        ]);

        $response = $this->actingAs($this->player)->getJson('/api/v1/plan/statistics?days=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'personal' // Игрок должен видеть только личную стату
                ]
            ])
            ->assertJsonMissingPath('data.team')
            ->assertJsonMissingPath('data.elite')
            ->assertJsonPath('data.personal.total_meetings', 5)
            ->assertJsonPath('data.personal.total_income', 100.50)
            ->assertJsonPath('data.personal.total_volume', 500)
            ->assertJsonPath('data.personal.active_days_count', 1);
    }

    public function test_leader_can_see_personal_and_team_statistics(): void
    {
        // Активность игрока в команде лидера
        DailyChecklist::factory()->create([
            'user_id' => $this->player->id,
            'date' => Carbon::today()->toDateString(),
            'is_completed' => true,
        ]);

        Contact::factory()->create([
            'user_id' => $this->player->id,
            'volume' => 300,
        ]);

        $response = $this->actingAs($this->leader)->getJson('/api/v1/plan/statistics?days=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'personal',
                    'team' // Лидер видит стату команды
                ]
            ])
            ->assertJsonMissingPath('data.elite')
            ->assertJsonPath('data.team.total_players', 1)
            ->assertJsonPath('data.team.active_players_today', 1)
            ->assertJsonPath('data.team.team_total_volume', 300);
    }

    public function test_elite_can_see_all_statistics_blocks(): void
    {
        // Активность лидера в команде элиты
        LeadershipChecklist::factory()->create([
            'user_id' => $this->leader->id,
            'date' => Carbon::today()->toDateString(),
            'is_completed' => true,
        ]);

        // Объем по сети (игрок лидера)
        Contact::factory()->create([
            'user_id' => $this->player->id,
            'volume' => 1000,
        ]);

        $response = $this->actingAs($this->elite)->getJson('/api/v1/plan/statistics?days=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'personal',
                    'team',
                    'elite' // Элита видит всё
                ]
            ])
            ->assertJsonPath('data.elite.total_leaders', 1)
            ->assertJsonPath('data.elite.active_leaders', 1)
            ->assertJsonPath('data.elite.total_team_volume', 1000);
    }
}
