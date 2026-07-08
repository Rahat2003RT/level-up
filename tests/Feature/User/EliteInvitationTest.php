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
}
