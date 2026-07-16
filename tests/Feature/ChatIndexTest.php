<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест: Лидер получает только свой чат с Лидером/Элитой и последним сообщением.
     */
    public function test_leader_can_get_their_single_chat_with_last_message(): void
    {
        // 1. Создаем пользователя-лидера и двух элит
        $leader = User::factory()->create(['role' => 'leader']); // Предполагается, что у тебя есть разделение ролей
        $elite1 = User::factory()->create(['role' => 'elite']);
        $elite2 = User::factory()->create(['role' => 'elite']);

        // 2. Создаем чат для нашего лидера и чужой чат
        $myChat = Chat::factory()->create([
            'leader_id' => $leader->id,
            'elite_id' => $elite1->id,
        ]);

        $otherChat = Chat::factory()->create([
            'leader_id' => User::factory()->create(['role' => 'leader'])->id,
            'elite_id' => $elite2->id,
        ]);

        // 3. Создаем сообщения для чата лидера (проверяем, что вернется именно последнее)
        Message::factory()->create([
            'chat_id' => $myChat->id,
            'text' => 'Старое сообщение',
            'created_at' => now()->subMinutes(10),
        ]);

        $latestMessage = Message::factory()->create([
            'chat_id' => $myChat->id,
            'text' => 'Самое последнее сообщение! 🔥',
            'created_at' => now(),
        ]);

        // 4. Делаем запрос под созданным лидером
        $response = $this->actingAs($leader)
            ->getJson('/api/v1/chats'); // Измени путь, если убрал api/v1

        // 5. Проверяем структуру и то, что Postgres не упал на UUID
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myChat->id)
            ->assertJsonPath('data.0.last_message.text', 'Самое последнее сообщение! 🔥');
    }

    /**
     * Тест: Элита получает список чатов с пагинацией и фильтрацией по данным лидера.
     */
    public function test_elite_can_index_and_search_chats_by_leader_details(): void
    {
        $elite = User::factory()->create(['role' => 'elite']);

        $leader1 = User::factory()->create([
            'role' => 'leader',
            'name' => 'Рахат',
            'surname' => 'Турмышов',
            'nickname' => 'rahat_dev'
        ]);

        $leader2 = User::factory()->create([
            'role' => 'leader',
            'name' => 'Иван',
            'surname' => 'Иванов',
            'nickname' => 'vanya'
        ]);

        // Чат, который должен найтись по поиску
        $chatTarget = Chat::factory()->create(['elite_id' => $elite->id, 'leader_id' => $leader1->id]);
        Message::factory()->create(['chat_id' => $chatTarget->id, 'text' => 'Привет от Рахата']);

        // Чат, который должен отсеяться фильтром
        $chatIgnore = Chat::factory()->create(['elite_id' => $elite->id, 'leader_id' => $leader2->id]);

        // Выполняем запрос с поисковым запросом query
        $response = $this->actingAs($elite)
            ->getJson('/api/v1/chats?query=Рахат');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $chatTarget->id)
            ->assertJsonPath('data.0.leader.name', 'Рахат');
    }
}
