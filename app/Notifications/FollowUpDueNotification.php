<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowUpDueNotification extends Notification
{
    use Queueable;

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
            'type' => 'follow_up_due',
            'lead_id' => $this->lead->id,
            'lead_label' => $label,
            'message' => "Follow-up due for {$label}.",
            'notes' => $this->lead->follow_up_notes,
            'url' => route('leads.show', $this->lead),
        ];
    }
}
