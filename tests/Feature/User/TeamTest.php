<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\TeamInvitation;
use App\Models\TeamPlan;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | ГЕНЕРАЦИЯ ИНВАЙТОВ (POST /api/v1/team/invitations)
    |--------------------------------------------------------------------------
    */

    public function test_leader_and_elite_can_generate_invite_links(): void
    {
        foreach ([UserRole::LEADER, UserRole::ELITE] as $role) {
            $user = User::factory()->create(['role' => $role]);
            Sanctum::actingAs($user);

            $response = $this->postJson('/api/v1/team/invitations');

            $response->assertSuccessful()
                ->assertJsonStructure(['data' => ['invite_url']]);

            $this->assertDatabaseHas('team_invitations', ['leader_id' => $user->id]);
        }
    }

    public function test_regular_player_cannot_generate_invite_links(): void
    {
        $user = User::factory()->create(['role' => UserRole::PLAYER]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/team/invitations');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | ПРАВИЛА ВСТУПЛЕНИЯ В КОМАНДУ (POST /api/v1/team/invitations/{token}/respond)
    |--------------------------------------------------------------------------
    */

    public function test_player_can_successfully_join_leader_team(): void
    {
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        $player = User::factory()->create(['role' => UserRole::PLAYER]);
        $invitation = TeamInvitation::factory()->create(['leader_id' => $leader->id]);

        Sanctum::actingAs($player);

        $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
            'accept' => true
        ]);

        $response->assertSuccessful()->assertJsonPath('data.status', 'accepted');
        $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => $leader->id]);
    }

    public function test_leader_can_successfully_join_elite_team(): void
    {
        $elite = User::factory()->create(['role' => UserRole::ELITE]);
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        $invitation = TeamInvitation::factory()->create(['leader_id' => $elite->id]);

        Sanctum::actingAs($leader);

        $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
            'accept' => true
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $leader->id, 'leader_id' => $elite->id]);
    }

    public function test_invalid_hierarchy_joins_are_blocked(): void
    {
        $scenarios = [
            ['user' => UserRole::PLAYER, 'inviter' => UserRole::ELITE],
            ['user' => UserRole::LEADER, 'inviter' => UserRole::LEADER],
            ['user' => UserRole::ELITE, 'inviter' => UserRole::LEADER],
        ];

        foreach ($scenarios as $scenario) {
            $inviter = User::factory()->create(['role' => $scenario['inviter']]);
            $user = User::factory()->create(['role' => $scenario['user']]);
            $invitation = TeamInvitation::factory()->create(['leader_id' => $inviter->id]);

            Sanctum::actingAs($user);

            $response = $this->postJson("/api/v1/team/invitations/{$invitation->token}/respond", [
                'accept' => true
            ]);

            $response->assertStatus(422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | СПИСОК ЧЛЕНОВ КОМАНДЫ (GET /api/v1/team/members)
    |--------------------------------------------------------------------------
    */

    public function test_leader_sees_unified_list_of_players(): void
    {
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        User::factory()->count(3)->create([
            'role' => UserRole::PLAYER,
            'leader_id' => $leader->id
        ]);

        Sanctum::actingAs($leader);

        $response = $this->getJson('/api/v1/team/members');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'avatar', 'role', 'current_day_number',
                        'progress_percent', 'status', 'total_players_count',
                        'active_players_count', 'monthly_volume', 'clients_count', 'partners_count'
                    ]
                ],
                'meta' => ['current_page', 'total']
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | КОМАНДНЫЙ ПЛАН (GET/PATCH /api/v1/team/plan)
    |--------------------------------------------------------------------------
    */

    public function test_leader_can_update_plan_but_player_cannot(): void
    {
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

        $planData = [
            'daily_calls' => 10,
            'daily_meetings' => 5,
            'business_conversations' => 0,
            'presentations' => 0,
            'social_media_posts' => 0,
            'new_clients_per_week' => 0,
            'new_partners_per_week' => 0,
            'daily_volume_points' => 0,
        ];

        // 1. Лидер (Ждем успешного создания/обновления)
        Sanctum::actingAs($leader);
        $this->patchJson('/api/v1/team/plan', $planData)->assertSuccessful();
        $this->assertDatabaseHas('team_plans', ['user_id' => $leader->id, 'daily_calls' => 10]);

        // 2. Игрок
        Sanctum::actingAs($player);
        $this->patchJson('/api/v1/team/plan', $planData)->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | ИСКЛЮЧЕНИЕ И ВЫХОД (DELETE /members/{id}, POST /leave)
    |--------------------------------------------------------------------------
    */

    public function test_leader_can_kick_player_from_team(): void
    {
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

        Sanctum::actingAs($leader);

        $response = $this->deleteJson("/api/v1/team/members/{$player->id}");

        $response->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => null]);
    }

    public function test_leader_cannot_kick_foreign_player(): void
    {
        $leaderA = User::factory()->create(['role' => UserRole::LEADER]);
        $leaderB = User::factory()->create(['role' => UserRole::LEADER]);
        $playerB = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leaderB->id]);

        Sanctum::actingAs($leaderA);

        $response = $this->deleteJson("/api/v1/team/members/{$playerB->id}");

        $response->assertStatus(422);
    }

    public function test_user_can_voluntarily_leave_team(): void
    {
        $leader = User::factory()->create(['role' => UserRole::LEADER]);
        $player = User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $leader->id]);

        Sanctum::actingAs($player);

        $response = $this->postJson('/api/v1/team/actions/leave');

        $response->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $player->id, 'leader_id' => null]);
    }
}
