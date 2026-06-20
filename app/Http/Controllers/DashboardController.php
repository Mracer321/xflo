<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the role-aware dashboard with workflow widgets.
     */
    public function index(): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'widgets' => $this->widgetsFor($user),
        ]);
    }

    /**
     * Build the dashboard widgets for the given user's role.
     *
     * @return array<int, array{label: string, value: int, tone: string}>
     */
    private function widgetsFor($user): array
    {
        if ($user->isDeveloper()) {
            $assigned = Lead::where('developer_id', $user->id);

            return [
                ['label' => 'Assigned Leads', 'value' => (clone $assigned)->count(), 'tone' => 'indigo'],
                ['label' => 'Demo In Progress', 'value' => (clone $assigned)->where('workflow_status', Lead::WF_DEMO_IN_PROGRESS)->count(), 'tone' => 'amber'],
                ['label' => 'Demo Ready Today', 'value' => (clone $assigned)->where('workflow_status', Lead::WF_DEMO_READY)->whereDate('demo_created_at', today())->count(), 'tone' => 'green'],
            ];
        }

        if ($user->hasRole(\App\Models\User::ROLE_SALES)) {
            return [
                ['label' => 'Demos Pending to Send', 'value' => Lead::where('workflow_status', Lead::WF_DEMO_READY)->count(), 'tone' => 'amber'],
                ['label' => 'Follow Ups Pending', 'value' => Lead::where('workflow_status', Lead::WF_FOLLOW_UP)->count(), 'tone' => 'indigo'],
                ['label' => 'Converted Leads', 'value' => Lead::where('workflow_status', Lead::WF_CONVERTED)->count(), 'tone' => 'green'],
            ];
        }

        // Super Admin and Leads Admin see the full pipeline overview.
        return [
            ['label' => 'Total Leads', 'value' => Lead::count(), 'tone' => 'indigo'],
            ['label' => 'Demo Ready', 'value' => Lead::where('workflow_status', Lead::WF_DEMO_READY)->count(), 'tone' => 'amber'],
            ['label' => 'Demo Sent', 'value' => Lead::where('workflow_status', Lead::WF_DEMO_SENT)->count(), 'tone' => 'blue'],
            ['label' => 'Converted', 'value' => Lead::where('workflow_status', Lead::WF_CONVERTED)->count(), 'tone' => 'green'],
            ['label' => 'Rejected', 'value' => Lead::where('workflow_status', Lead::WF_REJECTED)->count(), 'tone' => 'red'],
        ];
    }
}
