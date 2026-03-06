<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{

    public function __construct(public readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Reset your password — ' . config('app.name'))
            ->view('emails.reset-password', [
                'url'      => $url,
                'appName'  => config('app.name'),
                'email'    => $notifiable->getEmailForPasswordReset(),
                'expireMinutes' => config('auth.passwords.users.expire', 60),
            ]);
    }
}
