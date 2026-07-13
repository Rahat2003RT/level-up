<?php

declare(strict_types=1);

namespace Tests\Feature\Api\User;

use App\Models\Contact;
use App\Models\LeadershipChecklist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EliteStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $elite;
    protected User $leader1;
    protected User $leader2;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем Elite-пользователя
        $this->elite = User::factory()->create(['role' => 'elite']);

        // Привязываем лидеров к Elite (предполагаем, что связь идет через leader_id)
        $this->leader1 = User::factory()->create([
            'role' => 'leader',
            'leader_id' => $this->elite->id,
        ]);

        $this->leader2 = User::factory()->create([
            'role' => 'leader',
            'leader_id' => $this->elite->id,
        ]);

        // Создаем обычного игрока для первого лидера
        User::factory()->create([
            'role' => 'player',
            'leader_id' => $this->leader1->id,
        ]);
    }

    public function test_elite_can_get_overall_statistics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14'));

        // 1. Наполняем контакты объемами (volume) для лидеров и игроков
        Contact::factory()->create(['user_id' => $this->leader1->id, 'volume' => 10000]);
        Contact::factory()->create(['user_id' => $this->leader2->id, 'volume' => 20000]);

        // Игрок команды первого лидера
        $player = User::where('leader_id', $this->leader1->id)->where('role', 'player')->first();
        Contact::factory()->create(['user_id' => $player->id, 'volume' => 37000]); // Сумма: 67000

        // 2. Делаем лидера1 активным за сегодня
        LeadershipChecklist::factory()->create([
            'user_id' => $this->leader1->id,
            'date' => '2026-07-14',
            'is_completed' => true,
            'is_day_off' => false,
        ]);

        // Лидер2 сегодня не заполнил чек-лист (неактивен)

        $response = $this->actingAs($this->elite)
            ->getJson('/api/v1/elite/statistics');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_leaders', 2)
            ->assertJsonPath('data.active_leaders', 1)
            ->assertJsonPath('data.total_team_volume', 67000.0);

        Carbon::setTestNow();
    }

    public function test_non_elite_cannot_access_elite_statistics(): void
    {
        $regularLeader = User::factory()->create(['role' => 'leader']);

        $response = $this->actingAs($regularLeader)
            ->getJson('/api/v1/elite/statistics');

        $response->assertStatus(403);
    }
}
