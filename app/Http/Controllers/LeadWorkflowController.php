<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\ScheduleFollowUpRequest;
use App\Http\Requests\UpdateDemoStatusRequest;
use App\Http\Requests\UpdateLeadDemoRequest;
use App\Http\Requests\UpdateLeadSalesRequest;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Http\RedirectResponse;

class LeadWorkflowController extends Controller
{
    /**
     * Assign a developer to a lead and move it to "Assigned".
     */
    public function assign(AssignLeadRequest $request, Lead $lead): RedirectResponse
    {
        $developerId = (int) $request->validated()['developer_id'];
        $developer = User::find($developerId);

        // Capture the previously-assigned developer to tell "assigned" (first
        // time) apart from "changed" (reassignment) on the timeline.
        $previous = $lead->developer;
        $isReassignment = $previous && $previous->id !== $developerId;

        $lead->update([
            'developer_id' => $developerId,
            'workflow_status' => Lead::WF_ASSIGNED,
        ]);

        if ($isReassignment) {
            $lead->recordEvent(
                LeadEvent::TYPE_DEVELOPER_CHANGED,
                "Developer changed from {$previous->name} to {$developer?->name}.",
                $previous->name,
                $developer?->name,
            );
        } else {
            $lead->recordEvent(
                LeadEvent::TYPE_ASSIGNED,
                "Assigned to {$developer?->name}.",
                null,
                $developer?->name,
            );
        }

        // Notify the newly-assigned developer (skip if they assigned themselves).
        if ($developer && $developer->id !== auth()->id()) {
            $developer->notify(new LeadAssignedNotification($lead));
        }

        return back()->with('status', 'Developer assigned successfully.');
    }

    /**
     * Developer updates demo-related fields (URL, notes, demo status).
     */
    public function demoUpdate(UpdateLeadDemoRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = $data['workflow_status'];

        $previousStatus = $lead->workflow_status;
        $previousUrl = $lead->demo_url;
        $newUrl = $data['demo_url'] ?? null;

        $lead->demo_url = $newUrl;
        $lead->demo_notes = $data['demo_notes'] ?? null;

        // Stamp the demo creation time the first time it reaches "Demo Ready".
        if ($newStatus === Lead::WF_DEMO_READY && ! $lead->demo_created_at) {
            $lead->demo_created_at = now();
        }

        $statusChanged = $lead->workflow_status !== $newStatus;
        $lead->workflow_status = $newStatus;
        $lead->save();

        // A demo URL added for the first time is its own timeline event.
        if (empty($previousUrl) && ! empty($newUrl)) {
            $lead->recordEvent(LeadEvent::TYPE_DEMO_URL_ADDED, "Demo URL added: {$newUrl}", null, $newUrl);
        }

        if ($statusChanged) {
            $this->recordWorkflowEvent($lead, $newStatus, $previousStatus);
        }

        return back()->with('status', 'Demo details updated successfully.');
    }

    /**
     * Sales updates demo-sent / follow-up / final result fields.
     */
    public function salesUpdate(UpdateLeadSalesRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = $data['workflow_status'];

        $previousStatus = $lead->workflow_status;
        $lead->sales_notes = $data['sales_notes'] ?? null;

        // Stamp the demo-sent time the first time it is marked sent.
        if ($newStatus === Lead::WF_DEMO_SENT && ! $lead->demo_sent_at) {
            $lead->demo_sent_at = now();
        }

        // Stamp the demo-created time if an admin marks it ready from here.
        if ($newStatus === Lead::WF_DEMO_READY && ! $lead->demo_created_at) {
            $lead->demo_created_at = now();
        }

        $statusChanged = $lead->workflow_status !== $newStatus;
        $lead->workflow_status = $newStatus;
        $lead->save();

        if ($statusChanged) {
            $this->recordWorkflowEvent($lead, $newStatus, $previousStatus);
        }

        return back()->with('status', 'Sales details updated successfully.');
    }

    /**
     * Schedule (or reschedule) the next follow-up for a lead.
     *
     * The reminder is targeted at the scheduling user, and rescheduling
     * re-arms the reminder by clearing the "notified" stamp.
     */
    public function scheduleFollowUp(ScheduleFollowUpRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();

        $lead->next_follow_up_at = $data['next_follow_up_at'];
        $lead->follow_up_notes = $data['follow_up_notes'] ?? null;
        $lead->follow_up_user_id = auth()->id();
        // Re-arm: a freshly (re)scheduled follow-up has not been notified yet.
        $lead->follow_up_notified_at = null;
        $lead->save();

        $lead->recordEvent(
            LeadEvent::TYPE_FOLLOW_UP_SCHEDULED,
            'Follow-up scheduled for '.$lead->next_follow_up_at->format('M j, Y g:i A').'.',
            null,
            $lead->next_follow_up_at->toDateTimeString(),
        );

        return back()->with('status', 'Follow-up scheduled successfully.');
    }

    /**
     * Update the demo lifecycle status (Live <-> Offline).
     */
    public function demoStatusUpdate(UpdateDemoStatusRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = $data['demo_status'];
        $previous = $lead->demo_status;

        if ($newStatus === $previous) {
            return back()->with('status', 'Demo status is already '.Lead::DEMO_STATUSES[$newStatus].'.');
        }

        if ($newStatus === Lead::DEMO_OFFLINE) {
            $lead->offline_reason = $data['offline_reason'];
            $lead->offline_at = now();
            $lead->demo_status = Lead::DEMO_OFFLINE;
            $lead->save();
            $lead->recordEvent(LeadEvent::TYPE_DEMO_OFFLINE, 'Demo taken offline: '.$data['offline_reason']);

            return back()->with('status', 'Demo marked offline.');
        }

        // Back to Live (reactivation): clear the offline reason but keep timestamps for analytics history.
        $lead->demo_status = Lead::DEMO_LIVE;
        $lead->offline_reason = null;
        $lead->save();
        $lead->recordEvent(LeadEvent::TYPE_DEMO_REACTIVATED, 'Demo reactivated.');

        return back()->with('status', 'Demo reactivated.');
    }

    /**
     * Force-delete a demo record (Admin only). Marks the demo as deleted.
     */
    public function forceDeleteDemo(Lead $lead): RedirectResponse
    {
        $user = auth()->user();

        abort_unless(
            $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN]),
            403,
            'Only administrators can delete demo records.',
        );

        $lead->demo_status = Lead::DEMO_DELETED;
        $lead->deleted_at_demo = now();
        $lead->save();

        $lead->recordEvent(LeadEvent::TYPE_DEMO_DELETED, 'Demo record deleted.');

        return back()->with('status', 'Demo record deleted.');
    }

    /**
     * Record the timeline event for a workflow-status transition.
     *
     * Centralised so the developer and sales update paths stay consistent and
     * every stage maps to its correct event type and description.
     */
    private function recordWorkflowEvent(Lead $lead, string $status, ?string $previousStatus = null): void
    {
        $map = [
            Lead::WF_ASSIGNED => [LeadEvent::TYPE_ASSIGNED, 'Status set to Assigned.'],
            Lead::WF_DEMO_IN_PROGRESS => [LeadEvent::TYPE_DEMO_STARTED, 'Demo development started.'],
            Lead::WF_DEMO_READY => [LeadEvent::TYPE_DEMO_READY, 'Demo marked ready.'],
            Lead::WF_DEMO_SENT => [LeadEvent::TYPE_DEMO_SENT, 'Status updated to Demo Sent.'],
            Lead::WF_FOLLOW_UP => [LeadEvent::TYPE_FOLLOW_UP, 'Status updated to Follow Up.'],
            Lead::WF_CONVERTED => [LeadEvent::TYPE_CONVERTED, 'Status updated to Converted.'],
            Lead::WF_REJECTED => [LeadEvent::TYPE_REJECTED, 'Status updated to Rejected.'],
        ];

        if (! isset($map[$status])) {
            return;
        }

        [$type, $description] = $map[$status];

        // Record the human-readable labels for the status transition so the
        // timeline can show "Old → New" and Phase 6 analytics can group on it.
        $oldLabel = $previousStatus ? (Lead::WORKFLOW_STATUSES[$previousStatus] ?? $previousStatus) : null;
        $newLabel = Lead::WORKFLOW_STATUSES[$status] ?? $status;

        $lead->recordEvent($type, $description, $oldLabel, $newLabel);
    }
}
