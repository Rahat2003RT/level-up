<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $elite;
    protected User $leader;
    protected User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->elite = User::factory()->create(['role' => UserRole::ELITE]);
        $this->leader = User::factory()->create(['role' => UserRole::LEADER]);
        $this->player = User::factory()->create(['role' => UserRole::PLAYER]);
    }

    public function test_admin_can_see_commands_list()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/commands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'role', 'members_count']
                ],
                'meta' => ['current_page', 'last_page', 'total']
            ]);
    }

    public function test_admin_can_view_command_details_with_member_volumes()
    {
        $this->leader->update(['leader_id' => $this->elite->id]);
        $this->player->update(['leader_id' => $this->leader->id]);

        Contact::factory()->create(['user_id' => $this->player->id, 'volume' => 500]);
        Contact::factory()->create(['user_id' => $this->player->id, 'volume' => 300]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/commands/{$this->leader->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.elite_name', $this->elite->name)
            ->assertJsonPath('data.members.0.id', $this->player->id)
            ->assertJsonPath('data.members.0.volume', 800);
    }

    public function test_admin_can_add_valid_member_to_command()
    {
        $this->assertNull($this->player->leader_id);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/commands/{$this->leader->id}/add", [
                'member_id' => $this->player->id
            ]);

        // Изменено: теперь проверяем код 204 (успешный без контента), который отдает твой обновленный контроллер
        $response->assertStatus(204);

        $this->assertDatabaseHas('users', [
            'id' => $this->player->id,
            'leader_id' => $this->leader->id
        ]);
    }

    public function test_admin_cannot_add_leader_to_another_leader_command_hierarchy_validation()
    {
        $anotherLeader = User::factory()->create(['role' => UserRole::LEADER]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/commands/{$this->leader->id}/add", [
                'member_id' => $anotherLeader->id
            ]);

        // Изменено: ловим ValidationException, упакованное Ларавелем
        $response->assertStatus(422);
        $this->assertArrayHasKey('member', $response->json('errors'));
    }

    public function test_admin_can_search_available_users_for_command()
    {
        // Изменено: привязываем занятого игрока к существующему лидеру, избавляясь от ошибки FOREIGN KEY
        $anotherLeader = User::factory()->create(['role' => UserRole::LEADER]);
        User::factory()->create(['role' => UserRole::PLAYER, 'leader_id' => $anotherLeader->id]);

        $freePlayer = User::factory()->create(['role' => UserRole::PLAYER, 'name' => 'УникальноеИмя']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/commands/{$this->leader->id}/search-available?query=УникальноеИмя");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $freePlayer->id);
    }

    public function test_admin_can_kick_member_from_team()
    {
        $this->player->update(['leader_id' => $this->leader->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/commands/members/{$this->player->id}/kick");

        $response->assertStatus(204);

        $this->assertDatabaseHas('users', [
            'id' => $this->player->id,
            'leader_id' => null
        ]);
    }
}
