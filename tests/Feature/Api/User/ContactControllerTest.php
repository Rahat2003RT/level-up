<?php

namespace Tests\Feature\Api\User;

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем двух пользователей для проверки прав доступа
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /**
     * Тест получения списка контактов с фильтрацией и подсчетом объемов.
     */
    public function test_can_get_contacts_list_with_volumes(): void
    {
        // Создаем контакты для нашего пользователя
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'type' => ContactType::Client, // Убедитесь, что у вас есть такой кейс в Enums\ContactType
            'volume' => '1000.50',
            'name' => 'Иван Иванов',
        ]);

        Contact::factory()->create([
            'user_id' => $this->user->id,
            'type' => ContactType::Partner,
            'volume' => '500.00',
            'name' => 'Петр Петров',
        ]);

        // Создаем контакт чужого пользователя (он не должен влиять на наши объемы и список)
        Contact::factory()->create([
            'user_id' => $this->otherUser->id,
            'volume' => '9999.00',
        ]);

        // 1. Запрос без фильтров
        $response = $this->actingAs($this->user)
            ->getJson(route('contacts.index'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.filtered_volume', 1500.50)
            ->assertJsonPath('meta.total_volume', 1500.50);

        // 2. Запрос с фильтром по типу (например, только Client)
        $responseFiltered = $this->actingAs($this->user)
            ->getJson(route('contacts.index', ['type' => ContactType::Client->value]));

        $responseFiltered->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.filtered_volume', 1000.50) // Объем только клиентов
            ->assertJsonPath('meta.total_volume', 1500.50);   // Общий объем всех наших контактов

        // 3. Запрос с поисковым запросом query
        $responseSearch = $this->actingAs($this->user)
            ->getJson(route('contacts.index', ['query' => 'Петр']));

        $responseSearch->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Петр Петров')
            ->assertJsonPath('meta.filtered_volume', 500.00);
    }

    /**
     * Тест успешного создания нового контакта.
     */
    public function test_can_create_contact(): void
    {
        $payload = [
            'name' => 'Алексей Смирнов',
            'phone' => '+79991112233',
            'volume' => '2500',
            'comment' => 'Новый лид',
            'date_of_birth' => '1990-01-15',
            'type' => ContactType::Client->value,
            'reminder_at' => now()->addDays(2)->toIso8601String(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('contacts.store'), $payload);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Алексей Смирнов']);

        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'name' => 'Алексей Смирнов',
            'volume' => '2500',
        ]);
    }

    /**
     * Тест успешного обновления собственного контакта.
     */
    public function test_can_update_own_contact(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Старое Имя',
        ]);

        $payload = [
            'name' => 'Новое Имя',
            'volume' => '3000',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('contacts.update', $contact), $payload);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Новое Имя']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Новое Имя',
            'volume' => '3000',
        ]);
    }

    /**
     * Тест: нельзя обновить чужой контакт.
     */
    public function test_cannot_update_other_user_contact(): void
    {
        $contactOfOtherUser = Contact::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Чужой Контакт',
        ]);

        $payload = [
            'name' => 'Попытка Взлома',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('contacts.update', $contactOfOtherUser), $payload);

        // Ожидаем 403 Forbidden из-за проверки в UpdateRequest::authorize()
        $response->assertStatus(403);

        $this->assertDatabaseHas('contacts', [
            'id' => $contactOfOtherUser->id,
            'name' => 'Чужой Контакт', // Имя не изменилось
        ]);
    }

    /**
     * Тест успешного удаления собственного контакта.
     */
    public function test_can_delete_own_contact(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('contacts.destroy', $contact));

        $response->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /**
     * Тест: нельзя удалить чужой контакт.
     */
    public function test_cannot_delete_other_user_contact(): void
    {
        $contactOfOtherUser = Contact::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('contacts.destroy', $contactOfOtherUser));

        // Ожидаем 403 Forbidden из-за проверки внутри ContactService::deleteContact()
        $response->assertStatus(403);

        $this->assertDatabaseHas('contacts', [
            'id' => $contactOfOtherUser->id,
        ]);
    }
}
