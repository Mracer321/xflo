<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignLeadRequest;
use App\Http\Requests\UpdateDemoStatusRequest;
use App\Http\Requests\UpdateLeadDemoRequest;
use App\Http\Requests\UpdateLeadSalesRequest;
use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
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

        $lead->update([
            'developer_id'    => $developerId,
            'workflow_status' => Lead::WF_ASSIGNED,
        ]);

        $lead->recordEvent(LeadEvent::TYPE_ASSIGNED, "Assigned to {$developer?->name}.");

        return back()->with('status', 'Developer assigned successfully.');
    }

    /**
     * Developer updates demo-related fields (URL, notes, demo status).
     */
    public function demoUpdate(UpdateLeadDemoRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();
        $newStatus = $data['workflow_status'];

        $lead->demo_url = $data['demo_url'] ?? null;
        $lead->demo_notes = $data['demo_notes'] ?? null;

        // Stamp the demo creation time the first time it reaches "Demo Ready".
        if ($newStatus === Lead::WF_DEMO_READY && ! $lead->demo_created_at) {
            $lead->demo_created_at = now();
        }

        $statusChanged = $lead->workflow_status !== $newStatus;
        $lead->workflow_status = $newStatus;
        $lead->save();

        if ($statusChanged) {
            $lead->recordEvent(
                $newStatus === Lead::WF_DEMO_READY ? LeadEvent::TYPE_DEMO_READY : LeadEvent::TYPE_DEMO_STARTED,
                $newStatus === Lead::WF_DEMO_READY ? 'Demo marked ready.' : 'Demo development started.',
            );
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

        $lead->sales_notes = $data['sales_notes'] ?? null;

        // Stamp the demo-sent time the first time it is marked sent.
        if ($newStatus === Lead::WF_DEMO_SENT && ! $lead->demo_sent_at) {
            $lead->demo_sent_at = now();
        }

        $statusChanged = $lead->workflow_status !== $newStatus;
        $lead->workflow_status = $newStatus;
        $lead->save();

        if ($statusChanged) {
            $lead->recordEvent(
                self::SALES_EVENT_MAP[$newStatus],
                'Status updated to '.Lead::WORKFLOW_STATUSES[$newStatus].'.',
            );
        }

        return back()->with('status', 'Sales details updated successfully.');
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
     * Map a sales workflow status to its timeline event type.
     */
    private const SALES_EVENT_MAP = [
        Lead::WF_DEMO_SENT => LeadEvent::TYPE_DEMO_SENT,
        Lead::WF_FOLLOW_UP => LeadEvent::TYPE_FOLLOW_UP,
        Lead::WF_CONVERTED => LeadEvent::TYPE_CONVERTED,
        Lead::WF_REJECTED => LeadEvent::TYPE_REJECTED,
    ];
}
