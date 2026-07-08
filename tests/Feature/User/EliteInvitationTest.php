<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\TeamInvitation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EliteInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected User $elite;
    protected User $leaderUser;
    protected User $playerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем тестовых пользователей с нужными ролями
        $this->elite = User::factory()->create(['role' => 'elite']);
        $this->leaderUser = User::factory()->create(['role' => 'leader', 'leader_id' => null]);
        $this->playerUser = User::factory()->create(['role' => 'player']);
    }

    /**
     * Тест успешной генерации ссылки самой Элитой.
     */
    public function test_elite_can_successfully_generate_invitation_link()
    {
        $this->actingAs($this->elite, 'sanctum');

        $response = $this->postJson('/api/v1/elite/generate-invite');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['invite_url']]);

        $this->assertDatabaseHas('team_invitations', [
            'leader_id' => $this->elite->id,
        ]);
    }

    /**
     * Тест просмотра данных приглашения Лидером.
     */
    public function test_leader_can_view_elite_invitation_data_by_token()
    {
        $invitation = TeamInvitation::create([
            'leader_id' => $this->elite->id,
            'token' => 'elite-token-999',
            'expires_at' => Carbon::now()->addHours(1)
        ]);

        $this->actingAs($this->leaderUser, 'sanctum');

        $response = $this->getJson('/api/v1/leader/team-invitation/elite-token-999');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'elite_name' => $this->elite->name,
                    'token' => 'elite-token-999'
                ]
            ]);
    }

    /**
     * Тест защиты: Игрок (player) получает 422 ошибку при попытке вступить к Элите.
     */
    public function test_player_role_cannot_view_or_join_elite_team()
    {
        TeamInvitation::create([
            'leader_id' => $this->elite->id,
            'token' => 'elite-token-999',
            'expires_at' => Carbon::now()->addHours(1)
        ]);

        $this->actingAs($this->playerUser, 'sanctum');

        // Так как роут находится в префиксе leader под middleware can:access-leader,
        // игрок либо получит 403 от middleware, либо упадет на проверке роли в сервисе с 422.
        // Зависит от того, как у тебя настроен Gate для access-leader.
        // Если Gate пускает только лидеров, то проверяем assertStatus(403).
        // Если тест идет напрямую в метод сервиса, проверяем кастомный формат 422 ошибки:

        $response = $this->getJson('/api/v1/leader/team-invitation/elite-token-999');

        if ($response->status() === 403) {
            $response->assertStatus(403);
        } else {
            $response->assertStatus(422)
                ->assertJson([
                    'status' => 'error',
                    'message' => 'Only users with the Leader role can join an Elite team.'
                ]);
        }
    }

    /**
     * Тест успешного принятия инвайта Лидером.
     */
    public function test_leader_can_successfully_accept_elite_invitation()
    {
        TeamInvitation::create([
            'leader_id' => $this->elite->id,
            'token' => 'elite-token-999',
            'expires_at' => Carbon::now()->addHours(1)
        ]);

        $this->actingAs($this->leaderUser, 'sanctum');

        $response = $this->postJson('/api/v1/leader/team-invitation/elite-token-999/answer', [
            'accept' => true
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'accepted',
                'message' => 'You have successfully joined the Elite team.'
            ]);

        // Проверяем, что лидер теперь закреплен за элитой через leader_id
        $this->assertDatabaseHas('users', [
            'id' => $this->leaderUser->id,
            'leader_id' => $this->elite->id
        ]);
    }

    /**
     * Тест получения пагинированного списка лидеров для Элиты со всеми метриками.
     */
    public function test_elite_can_receive_paginated_leaders_with_correct_metrics()
    {
        $eliteUser = User::factory()->create(['role' => 'elite']);
        $this->actingAs($eliteUser, 'sanctum');

        // 1. Создаем лидера, привязанного к этой Элите
        $leader = User::factory()->create([
            'leader_id' => $eliteUser->id,
            'role' => 'leader',
            'name' => 'Tatyana',
            'surname' => 'Nikolayeva'
        ]);

        // 2. Создаем игроков для команды этого лидера (всего 2 игрока)
        $activePlayer = User::factory()->create(['leader_id' => $leader->id, 'role' => 'player']);
        $inactivePlayer = User::factory()->create(['leader_id' => $leader->id, 'role' => 'player']);

        // 3. Заполняем контакты для проверки Monthly Volume (700 + 300 + 200 = 1200)
        \App\Models\Contact::factory()->create(['user_id' => $leader->id, 'volume' => 700]);
        \App\Models\Contact::factory()->create(['user_id' => $activePlayer->id, 'volume' => 300]);
        \App\Models\Contact::factory()->create(['user_id' => $inactivePlayer->id, 'volume' => 200]);

        // 4. Задаем чек-лист для самого Лидера (15-й день курса, выполнен)
        \App\Models\LeadershipChecklist::create([
            'user_id' => $leader->id,
            'date' => \Carbon\Carbon::today()->toDateString(),
            'day_number' => 15,
            'is_completed' => true,
            'is_day_off' => false
        ]);

        // 5. Задаем чек-листы для игроков:
        // activePlayer — без пропусков (1 день курса = 1 чек-лист)
        \App\Models\DailyChecklist::create([
            'user_id' => $activePlayer->id,
            'date' => \Carbon\Carbon::today()->toDateString(),
            'day_number' => 1,
            'is_completed' => true
        ]);

        // inactivePlayer — пропустил день (в базе нет чек-листов, значит считается пропустившим)

        // 6. Выполняем запрос с явными параметрами пагинации
        $response = $this->getJson('/api/v1/elite/team-members?page=1&per_page=5');

        // 7. Проверяем структуру ответа, метаданные и точные расчеты по макету
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar',
                        'current_day_number',
                        'status',
                        'total_players_count',
                        'active_players_count',
                        'monthly_volume'
                    ]
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 1)
            // Проверка данных первой карточки Лидера
            ->assertJsonPath('data.0.id', $leader->id)
            ->assertJsonPath('data.0.name', 'Tatyana Nikolayeva')
            ->assertJsonPath('data.0.current_day_number', 15)
            ->assertJsonPath('data.0.status', 'Active')
            ->assertJsonPath('data.0.total_players_count', 2)   // Total Players: 2
            ->assertJsonPath('data.0.active_players_count', 1)  // Active Players (без пропусков): 1
            ->assertJsonPath('data.0.monthly_volume', 1200);   // Monthly Volume: 1200
    }
}
