<?php

namespace Tests\Feature\Admin;

use App\Enums\Period;
use App\Models\Tariff;
use App\Models\User; // Предполагаем, что у тебя есть модель пользователя
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем администратора для прохождения middleware can:access-admin
        // Настрой создание админа под реализацию своих сидеров/прав доступа
        $this->admin = User::factory()->create([
            'role' => 'admin', // или через spatie/laravel-permission, если используешь его
        ]);
    }

    /** @test */
    public function admin_can_get_tariffs_list_with_default_sorting(): void
    {
        // Создаем тарифы с конкретными ролями для проверки дефолтной сортировки
        Tariff::factory()->create(['role' => 'C_role']);
        Tariff::factory()->create(['role' => 'A_role']);
        Tariff::factory()->create(['role' => 'B_role']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/tariffs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'role', 'name', 'description', 'price', 'period', 'is_active', 'created_at', 'updated_at']
                ]
            ]);

        // Проверяем сортировку по умолчанию (по возрастанию роли: A, B, C)
        $data = $response->json('data');
        $this->assertEquals('A_role', $data[0]['role']);
        $this->assertEquals('B_role', $data[1]['role']);
        $this->assertEquals('C_role', $data[2]['role']);
    }

    /** @test */
    public function admin_can_sort_tariffs_by_price_descending(): void
    {
        Tariff::factory()->create(['price' => 100.00]);
        Tariff::factory()->create(['price' => 500.00]);
        Tariff::factory()->create(['price' => 300.00]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/tariffs?order_by=price&order_sort=desc');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(500.00, $data[0]['price']);
        $this->assertEquals(300.00, $data[1]['price']);
        $this->assertEquals(100.00, $data[2]['price']);
    }

    /** @test */
    public function admin_can_create_a_tariff(): void
    {
        $payload = [
            'role' => 'user',
            'name' => 'Premium Plan',
            'description' => 'Unlimited access to all features',
            'price' => 1499.99,
            'period' => Period::Month->value, // 'month'
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/tariffs', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Premium Plan',
                'price' => "1499.99", // decimal возвращается строкой
                'period' => 'month',
            ]);

        $this->assertDatabaseHas('tariffs', [
            'name' => 'Premium Plan',
            'price' => 1499.99,
        ]);
    }

    /** @test */
    public function it_validates_incorrect_period_on_creation(): void
    {
        $payload = [
            'name' => 'Invalid Plan',
            'price' => 99.00,
            'period' => 'incorrect_period_value', // Невалидный период
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/tariffs', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    /** @test */
    public function admin_can_update_a_tariff(): void
    {
        $tariff = Tariff::factory()->create([
            'name' => 'Old Name',
            'price' => 100.00,
            'period' => Period::Month,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'price' => 150.00,
            'period' => Period::Year->value, // '1year'
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/tariffs/{$tariff->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $tariff->id,
                'name' => 'Updated Name',
                'price' => "150.00",
                'period' => '1year',
            ]);

        $this->assertDatabaseHas('tariffs', [
            'id' => $tariff->id,
            'name' => 'Updated Name',
            'price' => 150.00,
        ]);
    }

    /** @test */
    public function admin_can_delete_a_tariff(): void
    {
        $tariff = Tariff::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/tariffs/{$tariff->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tariff successfully deleted.'
            ]);

        $this->assertDatabaseMissing('tariffs', [
            'id' => $tariff->id,
        ]);
    }

    /** @test */
    public function guest_cannot_access_tariff_endpoints(): void
    {
        // Проверяем, что неавторизованные пользователи получают 401/403
        $this->getJson('/api/v1/admin/tariffs')->assertStatus(401);
        $this->postJson('/api/v1/admin/tariffs', [])->assertStatus(401);
    }
}
