<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Attempt the delivery job up to three times before failing. */
    public int $tries = 3;

    /** Seconds to wait between retries. */
    public int $backoff = 30;

    public function __construct(public Lead $lead) {}

    /**
     * The channels the notification is delivered on (in-app only).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The payload stored in the notifications table and rendered by the UI.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $label = $this->lead->owner_name ?: $this->lead->business_name ?: "Lead #{$this->lead->id}";

        return [
            'type' => 'lead_assigned',
            'lead_id' => $this->lead->id,
            'lead_label' => $label,
            'message' => "You were assigned to lead {$label}.",
            'url' => route('leads.show', $this->lead),
        ];
    }
}
