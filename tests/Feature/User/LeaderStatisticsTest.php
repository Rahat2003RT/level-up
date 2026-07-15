<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\Contact;
use App\Models\DailyChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LeaderStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $activePlayer;
    protected User $inactivePlayer;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем лидера
        $this->leader = User::factory()->create([
            'role' => 'leader', // или как у тебя в проекте определяется роль/доступ
        ]);

        // Создаем игроков команды
        $this->activePlayer = User::factory()->create([
            'role' => 'player',
            'leader_id' => $this->leader->id,
        ]);

        $this->inactivePlayer = User::factory()->create([
            'role' => 'player',
            'leader_id' => $this->leader->id,
        ]);
    }

    public function test_leader_can_get_team_statistics_for_valid_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14'));

        // 1. Создаем контакты для игроков и наполняем объем (volume)
        Contact::factory()->create(['user_id' => $this->activePlayer->id, 'volume' => 3000]);
        Contact::factory()->create(['user_id' => $this->activePlayer->id, 'volume' => 1290]);
        Contact::factory()->create(['user_id' => $this->inactivePlayer->id, 'volume' => 3000]); // Всего: 7290

        // 2. Симулируем чек-листы за последние 30 дней.
        // Активный игрок заполнял всё вовремя или брал выходные (без пропусков)
        DailyChecklist::factory()->create([
            'user_id' => $this->activePlayer->id,
            'date' => '2026-07-13',
            'is_completed' => true,
            'is_day_off' => false,
        ]);

        // Неактивный (inactivePlayer) имеет пропуск (is_completed = false, is_day_off = false)
        DailyChecklist::factory()->create([
            'user_id' => $this->inactivePlayer->id,
            'date' => '2026-07-12',
            'is_completed' => false,
            'is_day_off' => false,
        ]);

        $response = $this->actingAs($this->leader)
            ->getJson('/api/v1/leader/statistics/team?days=30');

        $response->assertStatus(200)
            ->assertJsonPath('data.players_count', 2)
            ->assertJsonPath('data.active_players_count', 1) // Только activePlayer активен, у второго — пропуск
            ->assertJsonPath('data.total_volume', 7290)
            ->assertJsonPath('data.period_days', 30);

        Carbon::setTestNow(); // Сбрасываем фиксированное время
    }

    /** @test */
    public function statistics_fails_when_invalid_days_provided(): void
    {
        $response = $this->actingAs($this->leader)
            ->getJson('/api/v1/leader/statistics/team?days=15'); // 15 дней недопустимо

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['days']);
    }

    /** @test */
    public function regular_player_cannot_access_leader_statistics(): void
    {
        $player = User::factory()->create(['role' => 'player']);

        $response = $this->actingAs($player)
            ->getJson('/api/v1/leader/statistics/team?days=30');

        // Middleware 'can:access-leader' должен заблокировать этот запрос
        $response->assertStatus(403);
    }
}
