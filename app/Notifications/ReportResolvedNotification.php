<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Report $report;
    protected ?string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Report $report, ?string $message = null)
    {
        $this->report = $report;
        $this->message = $message;
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
        $status = $this->report->status;
        $statusLabel = match($status) {
            'resolved' => 'resolved',
            'closed' => 'closed',
            default => 'updated',
        };

        $title = "Your report has been {$statusLabel}";
        $message = $this->message ?? "Your report regarding {$this->report->target_type} has been {$statusLabel}.";

        return [
            'type' => 'report_status_updated',
            'title' => $title,
            'message' => $message,
            'report_id' => $this->report->id,
            'status' => $status,
            'status_label' => $statusLabel,
            'target_type' => $this->report->target_type,
            'target_id' => $this->report->target_id,
            'reason' => $this->report->reason,
            'admin_message' => $this->message,
            'updated_at' => $this->report->updated_at->toIso8601String(),
            'action_url' => url("/reports/{$this->report->id}"),
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
