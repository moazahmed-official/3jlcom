<?php

namespace App\Notifications;

use App\Models\FinditOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FindItOfferRejectedNotification extends Notification implements ShouldQueue
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
            ->subject("Update on your offer")
            ->greeting("Hello {$notifiable->name},")
            ->line("Unfortunately, your offer on \"{$finditRequest->title}\" was not accepted.")
            ->line("Offered Price: " . number_format($this->offer->price))
            ->line("Don't be discouraged! There are many other opportunities.")
            ->action('Browse FindIt Requests', url('/findit-requests'))
            ->line('Keep looking for your next deal!');
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
            'type' => 'findit_offer_rejected',
            'offer_id' => $this->offer->id,
            'findit_request_id' => $finditRequest->id,
            'findit_request_title' => $finditRequest->title,
            'offer_price' => $this->offer->price,
            'message' => "Your offer on \"{$finditRequest->title}\" was not accepted.",
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
            'title' => 'Offer Update',
            'body' => "Your offer on \"{$finditRequest->title}\" was not accepted",
            'data' => [
                'type' => 'findit_offer_rejected',
                'offer_id' => (string) $this->offer->id,
                'findit_request_id' => (string) $finditRequest->id,
                'click_action' => 'OPEN_MY_OFFERS',
            ],
            'priority' => 'normal',
        ];
    }
}
