<?php

namespace Tests\Feature\User;

use App\Models\LeadershipChecklist;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderChecklistAndPlanTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create(['role' => 'leader']);
        $this->player = User::factory()->create(['role' => 'player']);
    }

    // =========================================================================
    // 1. ТЕСТЫ ЧЕК-ЛИСТА ЛИДЕРА
    // =========================================================================

    public function test_leader_can_get_virtual_or_existing_checklist()
    {
        $this->actingAs($this->leader, 'sanctum');

        $response = $this->getJson('/api/v1/leader/checklist?date=' . Carbon::today()->toDateString());

        $response->assertStatus(200)
            ->assertJsonPath('data.is_completed', false);

        LeadershipChecklist::factory()->create([
            'user_id' => $this->leader->id,
            'date' => Carbon::today()->toDateString(),
            'checked_team_activity' => true
        ]);

        $response = $this->getJson('/api/v1/leader/checklist?date=' . Carbon::today()->toDateString());
        $response->assertStatus(200)
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.checked_team_activity', true);
    }

    public function test_leader_can_store_checklist_for_today()
    {
        $this->actingAs($this->leader, 'sanctum');

        $payload = [
            'checked_team_activity' => true,
            'contacted_players' => true,
            'added_new_player' => false,
            'held_online_meeting' => true,
            'posted_engaged_social_media' => false,
            'attracted_new_client' => true,
            'brought_new_partner' => false,
            'sent_new_invitations' => true,
            'notes_for_the_day' => 'Great day',
        ];

        $response = $this->postJson('/api/v1/leader/checklist', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('leadership_checklists', [
            'user_id' => $this->leader->id,
            'is_completed' => true,
        ]);
    }

    public function test_leader_cannot_store_checklist_twice_a_day()
    {
        $this->actingAs($this->leader, 'sanctum');

        LeadershipChecklist::factory()->create([
            'user_id' => $this->leader->id,
            'date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->postJson('/api/v1/leader/checklist', [
            'checked_team_activity' => true,
            'contacted_players' => true,
            'added_new_player' => false,
            'held_online_meeting' => false,
            'posted_engaged_social_media' => false,
            'attracted_new_client' => false,
            'brought_new_partner' => false,
            'sent_new_invitations' => false,
        ]);

        $response->assertStatus(403);
    }

    // =========================================================================
    // 2. ТЕСТЫ КОМАНДНОГО ПЛАНА (TEAM PLAN)
    // =========================================================================

    public function test_leader_can_create_and_update_team_plan()
    {
        $this->actingAs($this->leader, 'sanctum');

        $payload = [
            'daily_calls' => 10,
            'daily_meetings' => 3,
            'business_conversations' => 5,
            'presentations' => 2,
            'social_media_posts' => 1,
            'new_clients_per_week' => 4,
            'new_partners_per_week' => 2,
            'daily_volume_points' => 100,
        ];

        $response = $this->postJson('/api/v1/leader/team-plan', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.daily_calls', 10);

        $this->assertDatabaseHas('team_plans', [
            'user_id' => $this->leader->id,
            'daily_calls' => 10
        ]);
    }

    public function test_player_can_get_their_leaders_team_plan()
    {
        TeamPlan::create([
            'user_id' => $this->leader->id,
            'daily_calls' => 15,
            'daily_volume_points' => 200
        ]);

        $this->player->update(['leader_id' => $this->leader->id]);

        $this->actingAs($this->player, 'sanctum');

        $response = $this->getJson('/api/v1/player/team-plan');

        $response->assertStatus(200)
            ->assertJsonPath('data.daily_calls', 15)
            ->assertJsonPath('data.daily_volume_points', 200);
    }

    // =========================================================================
    // 3. ТЕСТЫ ВАЛИДАЦИИ ТОКЕНА И КОМАНДЫ
    // =========================================================================

    public function test_player_already_in_team_cannot_view_another_team_invitation()
    {
        $invitation = TeamInvitation::create([
            'leader_id' => $this->leader->id,
            'token' => 'test-token-12345',
            'expires_at' => Carbon::now()->addHours(1)
        ]);

        $anotherLeader = User::factory()->create(['role' => 'leader']);
        $this->player->update(['leader_id' => $anotherLeader->id]);

        $this->actingAs($this->player, 'sanctum');

        $response = $this->getJson('/api/v1/player/team-invitation/' . $invitation->token);

        // Проверяем ваш кастомный формат исключений
        $response->assertStatus(422)
            ->assertJson([
                'status'  => 'error',
                'message' => 'You are already a member of a team.',
            ]);
    }

    public function test_get_team_members_returns_correct_data_without_pagination()
    {
        $this->actingAs($this->leader, 'sanctum');
        $this->player->update(['leader_id' => $this->leader->id]);

        $response = $this->getJson('/api/v1/leader/team-members');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'current_day_number',
                        'progress_percent',
                        'clients_count',
                        'partners_count',
                        'is_completed_today'
                    ]
                ]
            ]);
    }

    public function test_player_can_successfully_leave_their_team()
    {
        // Привязываем игрока к лидеру
        $this->player->update(['leader_id' => $this->leader->id]);

        $this->actingAs($this->player, 'sanctum');

        $response = $this->postJson('/api/v1/player/leave-team');

        $response->assertStatus(204);

        // Проверяем, что в базе связь обнулилась
        $this->assertDatabaseHas('users', [
            'id'        => $this->player->id,
            'leader_id' => null
        ]);
    }

    public function test_player_cannot_leave_team_if_not_in_one()
    {
        // Убеждаемся, что лидер равен null
        $this->player->update(['leader_id' => null]);

        $this->actingAs($this->player, 'sanctum');

        $response = $this->postJson('/api/v1/player/leave-team');

        // Проверяем работу кастомного обработчика ошибок из bootstrap/app.php
        $response->assertStatus(422)
            ->assertJson([
                'status'  => 'error',
                'message' => 'You are not currently a member of any team.',
            ]);
    }

    public function test_leader_can_receive_correct_aggregated_dashboard_statistics()
    {
        $this->actingAs($this->leader, 'sanctum');

        // Создаем двух игроков в команде лидера
        $player1 = User::factory()->create(['leader_id' => $this->leader->id, 'role' => 'player']);
        $player2 = User::factory()->create(['leader_id' => $this->leader->id, 'role' => 'player']);

        // Игрок 1 заполнил 2 дня без пропусков (Активный)
        \App\Models\DailyChecklist::create(['user_id' => $player1->id, 'date' => Carbon::yesterday()->toDateString(), 'day_number' => 1, 'is_completed' => true]);
        \App\Models\DailyChecklist::create(['user_id' => $player1->id, 'date' => Carbon::today()->toDateString(), 'day_number' => 2, 'is_completed' => true]);

        // Игрок 2 имеет день 2, но заполнил всего 1 чек-лист (Был пропуск -> Не активный по ТЗ)
        \App\Models\DailyChecklist::create(['user_id' => $player2->id, 'date' => Carbon::today()->toDateString(), 'day_number' => 2, 'is_completed' => true]);

        $response = $this->getJson('/api/v1/leader/dashboard-statistics');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'players_count'    => 2,
                    'active_count'     => 1, // Только player1 прошел без пропусков дней
                    'average_progress' => 2, // (2/90)*100 = 2.2%, в среднем 2%
                ]
            ]);
    }

    public function test_get_team_members_returns_correct_summed_volume_as_integer()
    {
        $this->actingAs($this->leader, 'sanctum');

        // 1. Привязываем нашего игрока к лидеру
        $this->player->update(['leader_id' => $this->leader->id]);

        // 2. Создаем для этого игрока контакты с баллами volume (в виде целых чисел)
        // Убедись, что фабрика или модель Contact импортированы вверху файла тестов
        \App\Models\Contact::factory()->create([
            'user_id' => $this->player->id,
            'type'    => 'client',
            'volume'  => 150,
        ]);

        \App\Models\Contact::factory()->create([
            'user_id' => $this->player->id,
            'type'    => 'partner',
            'volume'  => 320,
        ]);

        // 3. Делаем запрос к эндпоинту получения списка участников
        $response = $this->getJson('/api/v1/leader/team-members');

        // 4. Проверяем статус и то, что сумма посчиталась верно (150 + 320 = 470)
        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $this->player->id)
            ->assertJsonPath('data.0.volume', 470);

        // 5. Дополнительная строгая проверка на тип данных (что это именно int, а не float/string)
        $responseData = $response->json('data.0');
        $this->assertIsInt($responseData['volume']);
    }


    public function test_leader_profile_returns_compact_team_info_with_correct_statuses()
    {
        $this->actingAs($this->leader, 'sanctum');

        // Создаем двух игроков для команды нашего Лидера
        $activePlayer = User::factory()->create(['leader_id' => $this->leader->id, 'role' => 'player']);
        $inactivePlayer = User::factory()->create(['leader_id' => $this->leader->id, 'role' => 'player']);

        // Накидываем им баллы через фабрику контактов (если у тебя настроена фабрика)
        \App\Models\Contact::factory()->create(['user_id' => $activePlayer->id, 'volume' => 200]);
        \App\Models\Contact::factory()->create(['user_id' => $inactivePlayer->id, 'volume' => 150]);

        // Настраиваем чек-листы:
        // ActivePlayer: последний заполненный день — успешный, не выходной
        \App\Models\DailyChecklist::create([
            'user_id' => $activePlayer->id,
            'date' => \Carbon\Carbon::today()->toDateString(),
            'day_number' => 5,
            'is_completed' => true,
            'is_day_off' => false,
        ]);

        // InactivePlayer: сегодня взял выходной (значит inactive)
        \App\Models\DailyChecklist::create([
            'user_id' => $inactivePlayer->id,
            'date' => \Carbon\Carbon::today()->toDateString(),
            'day_number' => 3,
            'is_completed' => false,
            'is_day_off' => true,
        ]);

        // Делаем запрос к профилю
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonPath('data.team_volume', 350) // 200 + 150
            ->assertJsonCount(2, 'data.team_members')
            // Проверяем выборочно поля первого игрока в списке
            ->assertJsonPath('data.team_members.0.id', $activePlayer->id)
            ->assertJsonPath('data.team_members.0.current_day_number', 5)
            ->assertJsonPath('data.team_members.0.progress_percent', 6) // round((5/90)*100) = 6%
            ->assertJsonPath('data.team_members.0.status', 'Active')
            // Проверяем статус неактивного игрока
            ->assertJsonPath('data.team_members.1.status', 'Inactive');

        // Проверяем, что лишние поля (типа контактов, планов и т.д.) не утекли в список team_members
        $response->assertJsonMissingPath('data.team_members.0.clients_count');
        $response->assertJsonMissingPath('data.team_members.0.volume');
    }
}
