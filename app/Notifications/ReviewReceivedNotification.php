<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Review $review;

    /**
     * Create a new notification instance.
     */
    public function __construct(Review $review)
    {
        $this->review = $review;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $reviewer = $this->review->user;
        $targetType = $this->review->target_type;
        $stars = $this->review->stars;

        $title = "New {$stars}-star review received";
        
        if ($targetType === 'ad') {
            $message = "{$reviewer->name} reviewed your ad";
            $adTitle = $this->review->ad->title ?? 'your ad';
            $excerpt = $this->review->body ? substr($this->review->body, 0, 100) : $this->review->title;
        } else {
            $message = "{$reviewer->name} reviewed you as a seller";
            $adTitle = null;
            $excerpt = $this->review->body ? substr($this->review->body, 0, 100) : $this->review->title;
        }

        return [
            'type' => 'review_received',
            'title' => $title,
            'message' => $message,
            'review_id' => $this->review->id,
            'reviewer_name' => $reviewer->name,
            'reviewer_id' => $reviewer->id,
            'stars' => $stars,
            'target_type' => $targetType,
            'ad_title' => $adTitle,
            'excerpt' => $excerpt,
            'created_at' => $this->review->created_at->toIso8601String(),
        ];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
