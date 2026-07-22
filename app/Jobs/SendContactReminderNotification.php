<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Contact;
use App\Models\User;
use App\Services\Mail\Firebase\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

final class SendContactReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Contact $contact
    ) {
    }

    public function handle(FcmService $fcmService): void
    {
        /** @var User|null $recipient */
        $recipient = $this->contact->user()->with('deviceTokens')->first();

        // Если пользователя нет, уведомления выключены или нет токенов — отбой
        if (!$recipient || !$recipient->notifications_enabled || $recipient->deviceTokens->isEmpty()) {
            return;
        }

        $locale = $recipient->locale ?? 'en';
        $contactName = trim((string)$this->contact->name) ?: 'Contact';

        // Получаем случайный заголовок и текст для нужной локали
        $messageData = $this->getRandomTemplate($locale, $contactName);

        foreach ($recipient->deviceTokens as $deviceToken) {
            $fcmService->sendToToken(
                token: $deviceToken->token,
                title: $messageData['title'],
                body: $messageData['body'],
                data: [
                    'action'     => 'CONTACT_REMINDER',
                    'contact_id' => (string)$this->contact->id,
                    'type'       => 'reminder'
                ]
            );
        }
    }

    /**
     * Возвращает случайный шаблон (1 из 5) с подставленным именем
     */
    private function getRandomTemplate(string $locale, string $name): array
    {
        $templates = [
            'ru' => [
                ['title' => 'Напоминание!', 'body' => "Пришло время связаться с {name}."],
                ['title' => 'Не забудьте!', 'body' => "У вас запланирован контакт с {name}."],
                ['title' => 'Время на исходе', 'body' => "Свяжитесь с {name} прямо сейчас."],
                ['title' => 'Держим связь', 'body' => "{name} ждет вашего сообщения или звонка."],
                ['title' => 'Пора действовать', 'body' => "Отличное время, чтобы пообщаться с {name}."]
            ],
            'en' => [
                ['title' => 'Reminder!', 'body' => "It's time to contact {name}."],
                ['title' => "Don't forget!", 'body' => "You have a scheduled contact with {name}."],
                ['title' => 'Time is ticking', 'body' => "Reach out to {name} right now."],
                ['title' => 'Follow up', 'body' => "{name} is waiting for your message or call."],
                ['title' => 'Time for action', 'body' => "It's a great time to catch up with {name}."]
            ],
            'es' => [
                ['title' => '¡Recordatorio!', 'body' => "Es hora de contactar a {name}."],
                ['title' => '¡No lo olvides!', 'body' => "Tienes un contacto programado con {name}."],
                ['title' => 'El tiempo corre', 'body' => "Comunícate con {name} ahora mismo."],
                ['title' => 'Seguimiento', 'body' => "{name} espera tu mensaje o llamada."],
                ['title' => 'Hora de actuar', 'body' => "Es un gran momento para hablar con {name}."]
            ],
            'pt' => [
                ['title' => 'Lembrete!', 'body' => "É hora de entrar em contato com {name}."],
                ['title' => 'Não se esqueça!', 'body' => "Você tem um contato agendado com {name}."],
                ['title' => 'O tempo está passando', 'body' => "Fale com {name} agora mesmo."],
                ['title' => 'Acompanhamento', 'body' => "{name} está esperando sua mensagem ou ligação."],
                ['title' => 'Hora de agir', 'body' => "É um ótimo momento para conversar com {name}."]
            ],
            'fr' => [
                ['title' => 'Rappel !', 'body' => "Il est temps de contacter {name}."],
                ['title' => "N'oubliez pas !", 'body' => "Vous avez un contact prévu avec {name}."],
                ['title' => 'Le temps presse', 'body' => "Contactez {name} dès maintenant."],
                ['title' => 'Suivi', 'body' => "{name} attend votre message ou appel."],
                ['title' => "Il est temps d'agir", 'body' => "C'est le bon moment pour prendre des nouvelles de {name}."]
            ],
            'de' => [
                ['title' => 'Erinnerung!', 'body' => "Es ist Zeit, {name} zu kontaktieren."],
                ['title' => 'Nicht vergessen!', 'body' => "Sie haben einen geplanten Kontakt mit {name}."],
                ['title' => 'Die Zeit läuft', 'body' => "Melden Sie sich jetzt bei {name}."],
                ['title' => 'Nachfassen', 'body' => "{name} wartet auf Ihre Nachricht oder Ihren Anruf."],
                ['title' => 'Zeit zu handeln', 'body' => "Es ist eine gute Zeit, sich mit {name} in Verbindung zu setzen."]
            ],
        ];

        // Защита от отсутствующей локали
        if (!array_key_exists($locale, $templates)) {
            $locale = 'en';
        }

        // Выбираем 1 рандомный массив (заголовок + тело)
        $randomTemplate = Arr::random($templates[$locale]);

        return [
            'title' => $randomTemplate['title'],
            'body'  => str_replace('{name}', $name, $randomTemplate['body'])
        ];
    }
}
