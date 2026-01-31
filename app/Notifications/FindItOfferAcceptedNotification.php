<?php

namespace App\Notifications;

use App\Models\FinditOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FindItOfferAcceptedNotification extends Notification implements ShouldQueue
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
        $requestOwner = $finditRequest->user;
        
        return (new MailMessage)
            ->subject("Your offer was accepted!")
            ->greeting("Congratulations {$notifiable->name}!")
            ->line("Your offer on \"{$finditRequest->title}\" has been accepted!")
            ->line("Offered Price: " . number_format($this->offer->price))
            ->line("The buyer's contact information is now available to you.")
            ->action('View Details', url("/findit-offers/{$this->offer->id}"))
            ->line('Contact the buyer to complete the transaction.');
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
            'type' => 'findit_offer_accepted',
            'offer_id' => $this->offer->id,
            'findit_request_id' => $finditRequest->id,
            'findit_request_title' => $finditRequest->title,
            'offer_price' => $this->offer->price,
            'buyer_id' => $finditRequest->user_id,
            'buyer_name' => $finditRequest->user->name ?? 'Unknown',
            'message' => "Your offer of " . number_format($this->offer->price) . " on \"{$finditRequest->title}\" was accepted!",
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
            'title' => 'Offer Accepted! 🎉',
            'body' => "Your offer on \"{$finditRequest->title}\" was accepted!",
            'data' => [
                'type' => 'findit_offer_accepted',
                'offer_id' => (string) $this->offer->id,
                'findit_request_id' => (string) $finditRequest->id,
                'click_action' => 'OPEN_FINDIT_OFFER_DETAILS',
            ],
            'priority' => 'high',
        ];
    }
}
