<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetPasswordLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $url,
        private readonly int $expiresInMinutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tautan untuk mengatur password akun Anda')
            ->line('Akun Anda telah dibuat. Gunakan tautan berikut untuk masuk dan mengatur password Anda.')
            ->action('Atur Password', $this->url)
            ->line("Tautan ini berlaku selama {$this->expiresInMinutes} menit dan hanya dapat digunakan sekali.")
            ->line('Jika Anda tidak meminta akun ini, abaikan email ini.');
    }
}
