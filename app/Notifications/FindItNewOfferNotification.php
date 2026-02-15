<?php

namespace App\Notifications;

use App\Models\FinditOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FindItNewOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public FinditOffer $offer
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // TODO: Add Firebase push notification channel when integrated
        // $channels[] = 'firebase';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $finditRequest = $this->offer->finditRequest;
        
        return (new MailMessage)
            ->subject("New offer on your FindIt request: {$finditRequest->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You received a new offer on your FindIt request.")
            ->line("Request: {$finditRequest->title}")
            ->line("Offered Price: " . number_format($this->offer->price))
            ->when($this->offer->comment, function ($message) {
                $message->line("Comment: {$this->offer->comment}");
            })
            ->action('View Offer', url("/findit/{$finditRequest->id}/offers"))
            ->line('Review the offer and respond to the seller.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $finditRequest = $this->offer->finditRequest;
        
        return [
            'type' => 'findit_new_offer',
            'offer_id' => $this->offer->id,
            'findit_request_id' => $finditRequest->id,
            'findit_request_title' => $finditRequest->title,
            'offer_price' => $this->offer->price,
            'offeror_id' => $this->offer->user_id,
            'offeror_name' => $this->offer->user->name ?? 'Unknown',
            'message' => "New offer of " . number_format($this->offer->price) . " on \"{$finditRequest->title}\"",
            'action_url' => url("/findit/{$finditRequest->id}/offers"),
        ];
    }

    /**
     * Get the Firebase push notification representation.
     * TODO: Implement when Firebase is integrated
     *
     * @return array<string, mixed>
     */
    public function toFirebase(object $notifiable): array
    {
        $finditRequest = $this->offer->finditRequest;
        
        return [
            'title' => 'New Offer Received!',
            'body' => "Someone offered " . number_format($this->offer->price) . " on your FindIt request",
            'data' => [
                'type' => 'findit_new_offer',
                'offer_id' => (string) $this->offer->id,
                'findit_request_id' => (string) $finditRequest->id,
                'click_action' => 'OPEN_FINDIT_OFFERS',
            ],
            'priority' => 'high',
        ];
    }
}
