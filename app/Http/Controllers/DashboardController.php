<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\StatsCache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the role-aware dashboard with workflow widgets.
     */
    public function index(): View
    {
        $user = auth()->user();

        // Widget counts are pure aggregates over leads; cache them per
        // role (developers additionally per-user, since they're scoped).
        $cacheKey = $user->isDeveloper()
            ? "dashboard:user:{$user->id}"
            : "dashboard:role:{$user->role}";

        $widgets = StatsCache::remember($cacheKey, fn () => $this->widgetsFor($user));

        return view('dashboard', [
            'widgets' => $widgets,
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
                ['label' => 'Demo Ready', 'value' => (clone $assigned)->where('workflow_status', Lead::WF_DEMO_READY)->count(), 'tone' => 'green'],
            ];
        }

        if ($user->hasRole(User::ROLE_SALES)) {
            return [
                ['label' => 'Demo Ready', 'value' => Lead::where('workflow_status', Lead::WF_DEMO_READY)->count(), 'tone' => 'amber'],
                ['label' => 'Demo Sent', 'value' => Lead::where('workflow_status', Lead::WF_DEMO_SENT)->count(), 'tone' => 'blue'],
                ['label' => 'Follow Ups Pending', 'value' => Lead::where('workflow_status', Lead::WF_FOLLOW_UP)->count(), 'tone' => 'indigo'],
                ['label' => 'Converted', 'value' => Lead::where('workflow_status', Lead::WF_CONVERTED)->count(), 'tone' => 'green'],
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
