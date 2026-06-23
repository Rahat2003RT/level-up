<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public string $token;
    public $locale;

    /**
     * Передаем токен и локаль пользователя
     */
    public function __construct(string $token, string $locale = 'en')
    {
        $this->token = $token;
        $this->locale = in_array($locale, ['ru', 'en', 'es', 'pt', 'fr', 'de']) ? $locale : 'en';
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('passwords.reset_subject', [], $this->locale))
            ->greeting(__('passwords.reset_greeting', [], $this->locale))
            ->line(__('passwords.reset_line_1', [], $this->locale))
            ->line(__('passwords.reset_line_2', [], $this->locale))
            ->salutation(__('passwords.reset_salutation', [], $this->locale));
    }
}
