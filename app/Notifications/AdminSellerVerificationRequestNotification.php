<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AdminSellerVerificationRequestNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $verificationRequest;

    public function __construct($user, $verificationRequest)
    {
        $this->user = $user;
        $this->verificationRequest = $verificationRequest;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Seller Verification Request')
            ->line("A new seller verification request has been submitted.")
            ->line("User: {$this->user->name} ({$this->user->email})")
            ->line("Phone: {$this->user->phone}")
            ->line("Account Type: {$this->user->account_type}")
            ->line("Please review the request in the admin dashboard.");
    }
}