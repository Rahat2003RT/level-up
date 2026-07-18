<?php

namespace Tests\Feature\Api\User;

use App\Enums\Period;
use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: Получение списка доступных тарифов для роли пользователя.
     */
    public function test_user_can_see_tariffs_available_for_their_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::LEADER,
        ]);

        $correctTariff = Tariff::factory()->create([
            'role'        => UserRole::LEADER->value,
            'is_active'   => true,
            'price'       => 100,
            'name'        => ['ru' => 'Стандартный', 'en' => 'Standard'],
            'description' => ['ru' => 'Описание', 'en' => 'Description'],
        ]);

        Tariff::factory()->create([
            'role'      => UserRole::ELITE->value,
            'is_active' => true,
            'name'      => ['ru' => 'Элита', 'en' => 'Elite'],
        ]);

        Tariff::factory()->create([
            'role'      => UserRole::LEADER->value,
            'is_active' => false,
            'name'      => ['ru' => 'Неактивный', 'en' => 'Inactive'],
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/tariffs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $correctTariff->id);
    }

    /**
     * Тест: Успешный выбор тарифа.
     */
    public function test_user_can_successfully_select_a_valid_tariff(): void
    {
        $user = User::factory()->create([
            'role'      => UserRole::LEADER,
            'tariff_id' => null,
        ]);

        $tariff = Tariff::factory()->create([
            'role'        => UserRole::LEADER->value,
            'is_active'   => true,
            'period'      => Period::Month,
            'name'        => ['ru' => 'Тариф', 'en' => 'Tariff'],
            'description' => ['ru' => 'Описание', 'en' => 'Description'],
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/tariffs/{$tariff->id}/select");

        // Проверяем твой кастомный 204 статус
        $response->assertStatus(204);

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'tariff_id'  => $tariff->id,
            'auto_renew' => true,
        ]);

        $user->refresh();
        $this->assertTrue($user->subscription_ends_at->isFuture());
    }

    /**
     * Тест: Нельзя выбрать тариф, предназначенный для другой роли.
     */
    public function test_user_cannot_select_tariff_of_another_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::PLAYER,
        ]);

        $tariff = Tariff::factory()->create([
            'role'      => UserRole::ELITE->value,
            'is_active' => true,
            'name'      => ['ru' => 'Тариф', 'en' => 'Tariff'],
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/tariffs/{$tariff->id}/select");

        $response->assertStatus(422);
    }

    /**
     * Тест: Успешная отмена автопродления подписки.
     */
    public function test_user_can_cancel_subscription_auto_renewal(): void
    {
        $tariff = Tariff::factory()->create([
            'name' => ['ru' => 'Тариф', 'en' => 'Tariff']
        ]);

        $user = User::factory()->create([
            'tariff_id'            => $tariff->id,
            'subscription_ends_at' => now()->addMonth(),
            'auto_renew'           => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tariffs/cancel');

        $response->assertStatus(204);

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'tariff_id'  => $tariff->id,
            'auto_renew' => false,
        ]);
    }

    /**
     * Тест: Нельзя отменить подписку, если тариф не выбран.
     */
    public function test_user_cannot_cancel_subscription_without_active_tariff(): void
    {
        $user = User::factory()->create([
            'tariff_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tariffs/cancel');

        $response->assertStatus(422);
    }

    /**
     * НОВЫЙ ТЕСТ: Проверка пуленепробиваемости TariffResource.
     * Проверяет, что если из фабрики/базы пришла строка вместо массива, ресурс не падает с 500 ошибкой.
     */
    public function test_tariff_resource_correctly_handles_string_instead_of_array(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::LEADER,
        ]);

        // Имитируем поведение SQLite/плохой фабрики, передавая чистые строки в JSON поля
        Tariff::factory()->create([
            'role'        => UserRole::LEADER->value,
            'is_active'   => true,
            'name'        => 'Fake String Name',
            'description' => 'Fake String Description',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/tariffs');

        // Ожидаем, что благодаря нашей проверке is_array ресурс вернет 200, а не 500
        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Fake String Name')
            ->assertJsonPath('data.0.description', 'Fake String Description');
    }
}
