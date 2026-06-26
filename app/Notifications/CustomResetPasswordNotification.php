<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class CustomResetPasswordNotification extends Notification
{
    use Queueable;

    public string $code;
    public $locale;

    public function __construct(string $code, string $locale = 'en')
    {
        $this->code = $code;
        $this->locale = in_array($locale, ['ru', 'en', 'es', 'pt', 'fr', 'de']) ? $locale : 'en';
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = __('passwords.reset_subject', [], $this->locale);
        $greeting = __('passwords.reset_greeting', [], $this->locale);
        $line1 = __('passwords.reset_line_1', [], $this->locale);
        $line2 = __('passwords.reset_line_2', [], $this->locale);
        $salutation = __('passwords.reset_salutation', [], $this->locale);

        $htmlContent = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . e($subject) . '</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; color: #334155; margin: 0; padding: 24px;">
        <div style="max-width: 576px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">

            <h1 style="font-size: 20px; font-weight: 600; color: #1e293b; margin-top: 0; margin-bottom: 24px;">' . e($greeting) . '</h1>

            <p style="font-size: 16px; line-height: 1.5; color: #475569; margin: 0 0 24px 0;">' . e($line1) . '</p>

            <div style="text-align: center; margin: 35px 0;">
                <div style="
                    display: inline-block;
                    background-color: #f3f4f6;
                    border: 2px dashed #cbd5e1;
                    border-radius: 12px;
                    padding: 16px 32px;
                    font-size: 32px;
                    font-family: monospace;
                    font-weight: 700;
                    letter-spacing: 6px;
                    color: #1e293b;
                    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
                ">' . e($this->code) . '</div>
            </div>

            <p style="font-size: 16px; line-height: 1.5; color: #475569; margin: 0 0 24px 0;">' . e($line2) . '</p>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 32px 0 24px 0;">

            <p style="font-size: 14px; color: #64748b; margin: 0; white-space: pre-line;">' . e($salutation) . '</p>
        </div>
    </body>
    </html>';

        return (new MailMessage)
            ->subject($subject)
            ->html($htmlContent);
    }
}
