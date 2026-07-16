<?php

namespace Tests\Feature\Admin\User;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Создаем админа для авторизации
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        // Создаем пользователей с разными ролями
        $player = User::factory()->create(['role' => UserRole::PLAYER]);
        $leader = User::factory()->create(['role' => UserRole::LEADER]);

        // Запрос только игроков
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=' . UserRole::PLAYER->value);

        $response->assertOk()
            ->assertJsonCount(1, 'data') // Ожидаем только одного игрока
            ->assertJsonPath('data.0.id', $player->id)
            ->assertJsonMissing(['id' => $leader->id]); // Лидера в списке быть не должно

        // Запрос только лидеров
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=' . UserRole::LEADER->value);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $leader->id)
            ->assertJsonMissing(['id' => $player->id]);
    }

    public function test_admin_cannot_filter_by_admin_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/users?role=' . UserRole::ADMIN->value);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'The selected role is invalid.'); // Проверяем сообщение
    }
}
