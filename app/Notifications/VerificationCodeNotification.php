<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Medimatch BD verification code')
            ->line('Use this code to verify your Medimatch BD account:')
            ->line('**'.$this->code.'**')
            ->line('The code expires in '.config('medimatch.verification_code_minutes').' minutes.')
            ->line('If you did not create an account, you can ignore this email.');
    }
}
