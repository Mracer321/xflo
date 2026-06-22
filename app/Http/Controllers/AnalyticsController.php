<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    /**
     * Role-aware analytics dashboard.
     *
     * Developers and sales see only their own figures; admins and leads
     * managers see team-wide figures, leaderboards and trends.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Period filter (drives leaderboards + the admin trend window scope).
        $period = $request->input('period', 'month');
        [$from, $to, $rangeLabel] = $this->analytics->resolveRange(
            $period, $request->input('date_from'), $request->input('date_to')
        );

        $data = [
            'role' => $user->role,
            'widgets' => $this->analytics->widgetsFor($user),
            'filters' => [
                'period' => $period,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'rangeLabel' => $rangeLabel,
        ];

        if ($user->isDeveloper()) {
            $data['metrics'] = $this->analytics->developerMetrics($user->id);
            $data['charts'] = [
                'Leads Worked Trend' => $this->analytics->trend($user->id, AnalyticsService::DEV_EVENTS, 14, true),
                'Demo Ready Trend' => $this->analytics->trend($user->id, ['demo_ready'], 14),
            ];
        } elseif ($user->hasRole(User::ROLE_SALES)) {
            $data['metrics'] = $this->analytics->salesMetrics($user->id);
            $data['charts'] = [
                'Demo Sent Trend' => $this->analytics->trend($user->id, ['demo_sent'], 14),
                'Conversion Trend' => $this->analytics->trend($user->id, ['converted'], 14),
            ];
        } else {
            // Super Admin & Leads Manager: full team analytics.
            $data['metrics'] = $this->analytics->adminMetrics();
            $data['developerLeaderboard'] = $this->analytics->developerLeaderboard($from, $to);
            $data['salesLeaderboard'] = $this->analytics->salesLeaderboard($from, $to);
            $data['charts'] = [
                'Leads Worked Trend' => $this->analytics->trend(null, AnalyticsService::WORK_EVENTS, 14, true),
                'Demo Ready Trend' => $this->analytics->trend(null, ['demo_ready'], 14),
                'Conversion Trend' => $this->analytics->trend(null, ['converted'], 14),
            ];
        }

        return view('analytics.index', $data);
    }

    /**
     * Team performance page — admins / leads managers only (route-gated).
     */
    public function team(Request $request): View
    {
        $period = $request->input('period', 'month');
        [$from, $to, $rangeLabel] = $this->analytics->resolveRange(
            $period, $request->input('date_from'), $request->input('date_to')
        );

        return view('analytics.team', [
            'developers' => $this->analytics->developerTeam($from, $to),
            'sales' => $this->analytics->salesTeam($from, $to),
            'rangeLabel' => $rangeLabel,
            'filters' => [
                'period' => $period,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ]);
    }
}
