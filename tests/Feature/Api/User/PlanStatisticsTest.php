<?php

namespace Tests\Feature\Api\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
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

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2024, 1, 15, 0, 0, 0));

        $this->player = User::factory()->create();
        $this->leader = User::factory()->create();

        // Настройка разрешений
        Gate::define('access-player', fn() => true);
        Gate::define('access-leader', fn(User $user) => $user->id === $this->leader->id);
    }

    public function test_player_can_see_personal_statistics(): void
    {
        DailyChecklist::factory()->create([
            'user_id' => $this->player->id,
            'date' => Carbon::today()->toDateString(),
            'is_completed' => true,
            'completed_meetings' => 5,
        ]);

        $response = $this->actingAs($this->player)->getJson('/api/v1/plan/statistics?days=10');

        $response->assertOk()
            ->assertJsonPath('data.personal.total_meetings', 5)
            ->assertJsonMissingPath('data.team'); // У игрока нет блока team
    }

    public function test_leader_can_see_personal_and_team_statistics(): void
    {
        // Добавляем игрока лидеру
        $playerInTeam = User::factory()->create(['leader_id' => $this->leader->id]);

        // Активность игрока в команде
        DailyChecklist::factory()->create([
            'user_id' => $playerInTeam->id,
            'date' => Carbon::today()->toDateString(),
            'is_completed' => true,
        ]);

        $response = $this->actingAs($this->leader)->getJson('/api/v1/plan/statistics?days=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'personal',
                    'team' => [
                        'total_players',
                        'active_players_today',
                        'team_total_volume',
                        'ranking'
                    ]
                ]
            ])
            ->assertJsonPath('data.team.total_players', 1)
            ->assertJsonPath('data.team.active_players_today', 1);
    }
}
