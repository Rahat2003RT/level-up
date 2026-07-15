<?php

declare(strict_types=1);

namespace Tests\Feature\Api\User;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\MessageUpdated;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
final class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $elite;
    private User $leader;
    private Chat $chat;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        // Создаем участников чата
        $this->elite = User::factory()->create();
        $this->leader = User::factory()->create();

        // Создаем приватный чат между ними
        $this->chat = Chat::create([
            'elite_id' => $this->elite->id,
            'leader_id' => $this->leader->id,
        ]);
    }

    /**
     * Тест: Успешная отправка сообщения и триггер события сокетов.
     */
    public function test_user_can_send_message(): void
    {
        Event::fake([MessageSent::class]);

        Sanctum::actingAs($this->elite);

        $payload = [
            'text' => 'Привет, это тестовое сообщение!',
        ];

        $response = $this->postJson("/api/v1/chats/{$this->chat->id}/messages", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.text', $payload['text'])
            ->assertJsonPath('data.sender_id', $this->elite->id);

        // Проверяем, что сообщение создалось в БД
        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'sender_id' => $this->elite->id,
            'text' => $payload['text'],
        ]);

        // Проверяем, что ивент MessageSent был отправлен в сокеты
        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($payload) {
            return $event->message->text === $payload['text']
                && (int) $event->message->sender_id === $this->elite->id;
        });
    }

    /**
     * Тест: Редактирование собственного сообщения.
     */
    public function test_user_can_update_own_message(): void
    {
        Event::fake([MessageUpdated::class]);

        Sanctum::actingAs($this->elite);

        // Сначала создаем сообщение от имени elite
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->elite->id,
            'text' => 'Старый текст',
        ]);

        $payload = [
            'text' => 'Новый измененный текст',
        ];

        $response = $this->patchJson("/api/v1/chats/{$this->chat->id}/messages/{$message->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.text', $payload['text']);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'text' => $payload['text'],
        ]);

        // Проверяем триггер ивента обновления
        Event::assertDispatched(MessageUpdated::class, function (MessageUpdated $event) use ($message, $payload) {
            return $event->message->id === $message->id
                && $event->message->text === $payload['text'];
        });
    }

    /**
     * Тест: Попытка редактирования чужого сообщения (должно вернуть 403 / Forbidden).
     */
    public function test_user_cannot_update_others_message(): void
    {
        // Создаем сообщение от имени leader
        $message = Message::create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->leader->id,
            'text' => 'Текст лидера',
        ]);

        // Авторизуемся под elite и пытаемся изменить сообщение лидера
        Sanctum::actingAs($this->elite);

        $response = $this->patchJson("/api/v1/chats/{$this->chat->id}/messages/{$message->id}", [
            'text' => 'Хакерская атака',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Тест: Пометка всех сообщений в чате прочитанными.
     */
    public function test_user_can_mark_messages_as_read(): void
    {
        Event::fake([MessagesRead::class]);

        Sanctum::actingAs($this->elite);

        // Создаем парочку непрочитанных сообщений в этом чате
        $message1 = Message::create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->leader->id,
            'text' => 'Сообщение 1',
            'read_at' => null,
        ]);

        $message2 = Message::create([
            'chat_id' => $this->chat->id,
            'sender_id' => $this->elite->id, // Твое новое правило считает абсолютно все сообщения, даже свои собственные
            'text' => 'Сообщение 2',
            'read_at' => null,
        ]);

        $response = $this->postJson("/api/v1/chats/{$this->chat->id}/messages/read");

        $response->assertStatus(200)
            ->assertJson([
                'read_count' => 2
            ]);

        // Проверяем, что в базе у обоих сообщений проставилась дата прочтения
        $this->assertDatabaseMissing('messages', [
            'chat_id' => $this->chat->id,
            'read_at' => null,
        ]);

        // Проверяем отправку ивента со списком UUID прочитанных сообщений
        Event::assertDispatched(MessagesRead::class, function (MessagesRead $event) use ($message1, $message2) {
            return $event->chatId === $this->chat->id
                && $event->readByUserId === $this->elite->id
                && in_array($message1->id, $event->messageIds, true)
                && in_array($message2->id, $event->messageIds, true);
        });
    }
}
