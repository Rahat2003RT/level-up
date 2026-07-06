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

        // Создаем лидера и игрока для тестов
        $this->leader = User::factory()->create(['role' => 'leader']);
        $this->player = User::factory()->create(['role' => 'player']);
    }

    // =========================================================================
    // 1. ТЕСТЫ ЧЕК-ЛИСТА ЛИДЕРА
    // =========================================================================

    /** @test */
    public function leader_can_get_virtual_or_existing_checklist()
    {
        $this->actingAs($this->leader, 'sanctum');

        // Проверяем получение виртуального чек-листа на сегодня (когда записи еще нет)
        $response = $this->getJson('/api/v1/leader/checklist?date=' . Carbon::today()->toDateString());

        $response->assertStatus(200)
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.checked_team_activity', false);

        // Создаем реальный чек-лист
        LeadershipChecklist::factory()->create([
            'user_id' => $this->leader->id,
            'date' => Carbon::today()->toDateString(),
            'checked_team_activity' => true
        ]);

        // Снова запрашиваем
        $response = $this->getJson('/api/v1/leader/checklist?date=' . Carbon::today()->toDateString());
        $response->assertStatus(200)
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.checked_team_activity', true);
    }

    /** @test */
    public function leader_can_store_checklist_for_today()
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

        $response->assertStatus(200);
        $this->assertDatabaseHas('leadership_checklists', [
            'user_id' => $this->leader->id,
            'is_completed' => true,
            'checked_team_activity' => true
        ]);
    }

    /** @test */
    public function leader_cannot_store_checklist_twice_a_day()
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

        // Должно вернуть 403 из-за AuthorizationException в сервисе
        $response->assertStatus(403);
    }

    // =========================================================================
    // 2. ТЕСТЫ КОМАНДНОГО ПЛАНА (TEAM PLAN)
    // =========================================================================

    /** @test */
    public function leader_can_create_and_update_team_plan()
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

        $response->assertStatus(200)
            ->assertJsonPath('data.daily_calls', 10);

        $this->assertDatabaseHas('team_plans', [
            'user_id' => $this->leader->id,
            'daily_calls' => 10
        ]);
    }

    /** @test */
    public function player_can_get_their_leaders_team_plan()
    {
        // Создаем план для лидера
        TeamPlan::create([
            'user_id' => $this->leader->id,
            'daily_calls' => 15,
            'daily_volume_points' => 200
        ]);

        // Привязываем игрока к этому лидеру
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

    /** @test */
    public function player_already_in_team_cannot_view_or_accept_another_team_invitation()
    {
        // Создаем инвайт от лидера
        $invitation = TeamInvitation::create([
            'leader_id' => $this->leader->id,
            'token' => 'test-token-12345',
            'expires_at' => Carbon::now()->addHours(1)
        ]);

        // Игрок УЖЕ состоит в какой-то другой команде
        $anotherLeader = User::factory()->create(['role' => 'leader']);
        $this->player->update(['leader_id' => $anotherLeader->id]);

        $this->actingAs($this->player, 'sanctum');

        // Пытаемся получить данные по токену
        $response = $this->getJson('/api/v1/player/team-invitation/' . $invitation->token);

        // Должна сработать наша новая валидация "вы уже состоите в команде" (422)
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['team']);
    }

    /** @test */
    public function get_team_members_returns_correct_data_without_pagination()
    {
        $this->actingAs($this->leader, 'sanctum');

        // Привязываем нашего игрока к лидеру
        $this->player->update(['leader_id' => $this->leader->id]);

        $response = $this->getJson('/api/v1/leader/team-members');

        // Проверяем, что в структуре ответа нет ключей пагинации (links, meta), а есть чистый массив данных
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
}
