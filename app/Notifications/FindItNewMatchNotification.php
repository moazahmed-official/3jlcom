<?php

namespace App\Notifications;

use App\Models\FinditMatch;
use App\Models\FinditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FindItNewMatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public FinditRequest $finditRequest,
        public int $newMatchesCount,
        public ?FinditMatch $topMatch = null
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
        return (new MailMessage)
            ->subject("New matches for your FindIt request: {$this->finditRequest->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("We found {$this->newMatchesCount} new matching cars for your search request.")
            ->line("Request: {$this->finditRequest->title}")
            ->when($this->topMatch, function ($message) {
                $message->line("Top match score: {$this->topMatch->match_score}%");
            })
            ->action('View Matches', url("/findit/{$this->finditRequest->id}/matches"))
            ->line('Check out the matches to find your perfect car!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'findit_new_match',
            'findit_request_id' => $this->finditRequest->id,
            'findit_request_title' => $this->finditRequest->title,
            'new_matches_count' => $this->newMatchesCount,
            'top_match_score' => $this->topMatch?->match_score,
            'top_match_ad_id' => $this->topMatch?->ad_id,
            'message' => "Found {$this->newMatchesCount} new matches for \"{$this->finditRequest->title}\"",
            'action_url' => url("/findit/{$this->finditRequest->id}/matches"),
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
        return [
            'title' => 'New Car Matches Found!',
            'body' => "Found {$this->newMatchesCount} cars matching your search \"{$this->finditRequest->title}\"",
            'data' => [
                'type' => 'findit_new_match',
                'findit_request_id' => (string) $this->finditRequest->id,
                'new_matches_count' => (string) $this->newMatchesCount,
                'click_action' => 'OPEN_FINDIT_MATCHES',
            ],
            'priority' => 'high',
        ];
    }
}
