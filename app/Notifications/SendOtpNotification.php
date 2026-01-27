<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    use Queueable;

    protected $otp;
    protected $purpose;

    public function __construct(string $otp, string $purpose = 'verification')
    {
        $this->otp = $otp;
        $this->purpose = $purpose;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $subject = 'Your ' . ucfirst($this->purpose) . ' Code';
        
        return (new MailMessage)
            ->subject($subject)
            ->line("Your OTP code for {$this->purpose} is:")
            ->line("**{$this->otp}**")
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request this code, please ignore this message.');
    }
}