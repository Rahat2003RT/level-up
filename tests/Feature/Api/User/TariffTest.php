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
            'role'      => UserRole::LEADER->value,
            'is_active' => true,
            'price'     => 100,
        ]);

        Tariff::factory()->create([
            'role'      => UserRole::ELITE->value,
            'is_active' => true,
        ]);

        Tariff::factory()->create([
            'role'      => UserRole::LEADER->value,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/tariffs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $correctTariff->id);
    }

    /**
     * Тест: Успешный выбор тарифа с динамическим расчетом даты окончания (на примере месяца).
     */
    public function test_user_can_successfully_select_a_valid_tariff(): void
    {
        $user = User::factory()->create([
            'role'      => UserRole::LEADER,
            'tariff_id' => null,
        ]);

        $tariff = Tariff::factory()->create([
            'role'      => UserRole::LEADER->value,
            'is_active' => true,
            'period'    => Period::Month,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/tariffs/{$tariff->id}/select");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tariff successfully selected.']);

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'tariff_id'  => $tariff->id,
            'auto_renew' => true,
        ]);

        $user->refresh();
        $this->assertTrue($user->subscription_ends_at->isFuture());
        $this->assertEquals(
            now()->addMonth()->startOfMinute()->toDateTimeString(),
            $user->subscription_ends_at->startOfMinute()->toDateTimeString()
        );
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
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/tariffs/{$tariff->id}/select");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tariff']);
    }

    /**
     * Тест: Успешная отмена автопродления подписки.
     */
    public function test_user_can_cancel_subscription_auto_renewal(): void
    {
        $tariff = Tariff::factory()->create();

        $user = User::factory()->create([
            'tariff_id'            => $tariff->id,
            'subscription_ends_at' => now()->addMonth(),
            'auto_renew'           => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/tariffs/cancel');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Subscription auto-renewal has been successfully cancelled.']);

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

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subscription']);
    }
}
