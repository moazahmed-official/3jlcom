<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
    // Removed ShouldQueue interface for immediate sending
    // use Queueable;

    protected array $notificationData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->notificationData = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->notificationData['title'],
            'body' => $this->notificationData['body'],
            'message' => $this->notificationData['body'], // Alias for compatibility
            'data' => $this->notificationData['data'] ?? [],
            'action_url' => $this->notificationData['action_url'] ?? null,
            // Optional image URL (string) if provided by admin
            'image' => $this->notificationData['image'] ?? null,
            'sent_by' => $this->notificationData['sent_by'] ?? null,
            'type' => 'admin_message',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->notificationData['title'])
            ->line($this->notificationData['body']);

        if (!empty($this->notificationData['action_url'])) {
            $mail->action('View Details', $this->notificationData['action_url']);
        }

        return $mail;
    }
}
