<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use App\Services\StatsCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Phase 6 — productivity & conversion analytics.
 *
 * Every figure is derived from existing data (lead_events, leads.workflow_status,
 * leads.developer_id) via aggregated GROUP BY queries — no per-row loops and no
 * duplicate tracking tables. Developer/sales productivity is attributed through
 * the actor (lead_events.user_id) that generated each timeline event.
 */
class AnalyticsService
{
    /** Timeline events that represent developer work. */
    public const DEV_EVENTS = [
        LeadEvent::TYPE_DEMO_STARTED,
        LeadEvent::TYPE_DEMO_READY,
        LeadEvent::TYPE_DEMO_URL_ADDED,
    ];

    /** Timeline events that represent sales work. */
    public const SALES_EVENTS = [
        LeadEvent::TYPE_DEMO_SENT,
        LeadEvent::TYPE_FOLLOW_UP,
        LeadEvent::TYPE_CONVERTED,
        LeadEvent::TYPE_REJECTED,
    ];

    /** All events that count as "working" a lead. */
    public const WORK_EVENTS = [
        LeadEvent::TYPE_DEMO_STARTED,
        LeadEvent::TYPE_DEMO_READY,
        LeadEvent::TYPE_DEMO_URL_ADDED,
        LeadEvent::TYPE_DEMO_SENT,
        LeadEvent::TYPE_FOLLOW_UP,
        LeadEvent::TYPE_CONVERTED,
        LeadEvent::TYPE_REJECTED,
    ];

    /**
     * Resolve a period key (+ optional custom range) to a [from, to] window.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string} [from, to, label]
     */
    public function resolveRange(string $period, ?string $from = null, ?string $to = null): array
    {
        return match ($period) {
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'This Week'],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'This Month'],
            'custom' => [
                ($from ? Carbon::parse($from) : Carbon::now())->startOfDay(),
                ($to ? Carbon::parse($to) : Carbon::now())->endOfDay(),
                'Custom Range',
            ],
            default => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay(), 'Today'],
        };
    }

    /**
     * Count events per type for one actor (or everyone) within a window.
     *
     * @return array<string, int> type => count (missing types are absent)
     */
    public function typeCounts(?int $userId, Carbon $from, Carbon $to): array
    {
        return LeadEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Distinct leads an actor (or everyone) worked on within a window.
     */
    public function leadsWorked(?int $userId, Carbon $from, Carbon $to, array $types = self::WORK_EVENTS): int
    {
        return LeadEvent::query()
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$from, $to])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->distinct()
            ->count('lead_id');
    }

    /**
     * Developer dashboard blocks (today / this week / this month) for one user.
     *
     * @return array<string, array<string, int>>
     */
    public function developerMetrics(int $userId): array
    {
        return StatsCache::remember("analytics:devmetrics:{$userId}", fn () => $this->computeDeveloperMetrics($userId));
    }

    private function computeDeveloperMetrics(int $userId): array
    {
        [$todayFrom, $todayTo] = $this->resolveRange('today');
        [$weekFrom, $weekTo] = $this->resolveRange('week');
        [$monthFrom, $monthTo] = $this->resolveRange('month');

        $today = $this->typeCounts($userId, $todayFrom, $todayTo);
        $week = $this->typeCounts($userId, $weekFrom, $weekTo);
        $month = $this->typeCounts($userId, $monthFrom, $monthTo);

        return [
            'today' => [
                'Leads Worked' => $this->leadsWorked($userId, $todayFrom, $todayTo, self::DEV_EVENTS),
                'Demo In Progress' => $today[LeadEvent::TYPE_DEMO_STARTED] ?? 0,
                'Demo Ready' => $today[LeadEvent::TYPE_DEMO_READY] ?? 0,
                'Demos Completed' => $today[LeadEvent::TYPE_DEMO_READY] ?? 0,
            ],
            'week' => [
                'Leads Worked' => $this->leadsWorked($userId, $weekFrom, $weekTo, self::DEV_EVENTS),
                'Demos Completed' => $week[LeadEvent::TYPE_DEMO_READY] ?? 0,
            ],
            'month' => [
                'Leads Worked' => $this->leadsWorked($userId, $monthFrom, $monthTo, self::DEV_EVENTS),
                'Demos Completed' => $month[LeadEvent::TYPE_DEMO_READY] ?? 0,
            ],
        ];
    }

    /**
     * Sales dashboard blocks (today / this week / this month) for one user.
     *
     * @return array<string, array<string, int>>
     */
    public function salesMetrics(int $userId): array
    {
        return StatsCache::remember("analytics:salesmetrics:{$userId}", fn () => $this->computeSalesMetrics($userId));
    }

    private function computeSalesMetrics(int $userId): array
    {
        [$todayFrom, $todayTo] = $this->resolveRange('today');
        [$weekFrom, $weekTo] = $this->resolveRange('week');
        [$monthFrom, $monthTo] = $this->resolveRange('month');

        $today = $this->typeCounts($userId, $todayFrom, $todayTo);
        $week = $this->typeCounts($userId, $weekFrom, $weekTo);
        $month = $this->typeCounts($userId, $monthFrom, $monthTo);

        return [
            'today' => [
                'Demo Sent' => $today[LeadEvent::TYPE_DEMO_SENT] ?? 0,
                'Follow Ups Done' => $today[LeadEvent::TYPE_FOLLOW_UP] ?? 0,
                'Converted Leads' => $today[LeadEvent::TYPE_CONVERTED] ?? 0,
                'Rejected Leads' => $today[LeadEvent::TYPE_REJECTED] ?? 0,
            ],
            'week' => [
                'Demo Sent' => $week[LeadEvent::TYPE_DEMO_SENT] ?? 0,
                'Conversions' => $week[LeadEvent::TYPE_CONVERTED] ?? 0,
            ],
            'month' => [
                'Demo Sent' => $month[LeadEvent::TYPE_DEMO_SENT] ?? 0,
                'Conversions' => $month[LeadEvent::TYPE_CONVERTED] ?? 0,
            ],
        ];
    }

    /**
     * Admin dashboard blocks (today / this week / this month), team-wide.
     *
     * @return array<string, array<string, int|string>>
     */
    public function adminMetrics(): array
    {
        return StatsCache::remember('analytics:adminmetrics', fn () => $this->computeAdminMetrics());
    }

    private function computeAdminMetrics(): array
    {
        [$todayFrom, $todayTo] = $this->resolveRange('today');
        [$weekFrom, $weekTo] = $this->resolveRange('week');
        [$monthFrom, $monthTo] = $this->resolveRange('month');

        $today = $this->typeCounts(null, $todayFrom, $todayTo);
        $week = $this->typeCounts(null, $weekFrom, $weekTo);
        $month = $this->typeCounts(null, $monthFrom, $monthTo);

        $monthLeads = $month[LeadEvent::TYPE_CREATED] ?? 0;
        $monthConversions = $month[LeadEvent::TYPE_CONVERTED] ?? 0;

        return [
            'today' => [
                'Total Leads Worked' => $this->leadsWorked(null, $todayFrom, $todayTo),
                'Demo Ready' => $today[LeadEvent::TYPE_DEMO_READY] ?? 0,
                'Demo Sent' => $today[LeadEvent::TYPE_DEMO_SENT] ?? 0,
                'Converted' => $today[LeadEvent::TYPE_CONVERTED] ?? 0,
                'Rejected' => $today[LeadEvent::TYPE_REJECTED] ?? 0,
            ],
            'week' => [
                'Leads Worked' => $this->leadsWorked(null, $weekFrom, $weekTo),
                'Demos Ready' => $week[LeadEvent::TYPE_DEMO_READY] ?? 0,
                'Demo Sent' => $week[LeadEvent::TYPE_DEMO_SENT] ?? 0,
                'Conversions' => $week[LeadEvent::TYPE_CONVERTED] ?? 0,
            ],
            'month' => [
                'Total Leads' => $monthLeads,
                'Total Demos' => $month[LeadEvent::TYPE_DEMO_READY] ?? 0,
                'Total Conversions' => $monthConversions,
                'Conversion Rate' => $monthLeads > 0 ? round($monthConversions / $monthLeads * 100).'%' : '0%',
            ],
        ];
    }

    /**
     * Per-user counts for a set of event types within a window.
     *
     * @return array<int, array<string, int>> user_id => [type => count]
     */
    private function perUserTypeCounts(array $types, Carbon $from, Carbon $to): array
    {
        $matrix = [];

        LeadEvent::query()
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, type, COUNT(*) as aggregate')
            ->groupBy('user_id', 'type')
            ->get()
            ->each(function ($row) use (&$matrix) {
                $matrix[$row->user_id][$row->type] = (int) $row->aggregate;
            });

        return $matrix;
    }

    /**
     * Per-user distinct leads worked within a window.
     *
     * @return array<int, int> user_id => distinct leads
     */
    private function perUserLeadsWorked(array $types, Carbon $from, Carbon $to): array
    {
        return LeadEvent::query()
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(DISTINCT lead_id) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Developer team rows: name, assigned, leads worked, demo ready, converted.
     */
    public function developerTeam(Carbon $from, Carbon $to): Collection
    {
        $key = "analytics:devteam:{$from->timestamp}:{$to->timestamp}";

        return StatsCache::remember($key, fn () => $this->computeDeveloperTeam($from, $to));
    }

    private function computeDeveloperTeam(Carbon $from, Carbon $to): Collection
    {
        $developers = User::where('role', User::ROLE_DEVELOPER)->orderBy('name')->get();

        $assigned = Lead::whereNotNull('developer_id')
            ->selectRaw('developer_id, COUNT(*) as aggregate')
            ->groupBy('developer_id')->pluck('aggregate', 'developer_id');

        $converted = Lead::whereNotNull('developer_id')
            ->where('workflow_status', Lead::WF_CONVERTED)
            ->selectRaw('developer_id, COUNT(*) as aggregate')
            ->groupBy('developer_id')->pluck('aggregate', 'developer_id');

        $counts = $this->perUserTypeCounts([LeadEvent::TYPE_DEMO_READY], $from, $to);
        $worked = $this->perUserLeadsWorked(self::DEV_EVENTS, $from, $to);

        return $developers->map(fn (User $dev) => [
            'name' => $dev->name,
            'assigned' => (int) ($assigned[$dev->id] ?? 0),
            'leads_worked' => (int) ($worked[$dev->id] ?? 0),
            'demo_ready' => (int) ($counts[$dev->id][LeadEvent::TYPE_DEMO_READY] ?? 0),
            'converted' => (int) ($converted[$dev->id] ?? 0),
        ]);
    }

    /**
     * Sales team rows: name, follow ups, demo sent, conversions.
     */
    public function salesTeam(Carbon $from, Carbon $to): Collection
    {
        $key = "analytics:salesteam:{$from->timestamp}:{$to->timestamp}";

        return StatsCache::remember($key, fn () => $this->computeSalesTeam($from, $to));
    }

    private function computeSalesTeam(Carbon $from, Carbon $to): Collection
    {
        $sales = User::where('role', User::ROLE_SALES)->orderBy('name')->get();
        $counts = $this->perUserTypeCounts(self::SALES_EVENTS, $from, $to);

        return $sales->map(fn (User $rep) => [
            'name' => $rep->name,
            'follow_ups' => (int) ($counts[$rep->id][LeadEvent::TYPE_FOLLOW_UP] ?? 0),
            'demo_sent' => (int) ($counts[$rep->id][LeadEvent::TYPE_DEMO_SENT] ?? 0),
            'conversions' => (int) ($counts[$rep->id][LeadEvent::TYPE_CONVERTED] ?? 0),
        ]);
    }

    /**
     * Developer leaderboard sorted by leads worked (with demo-ready tally).
     */
    public function developerLeaderboard(Carbon $from, Carbon $to): Collection
    {
        return $this->developerTeam($from, $to)
            ->sortByDesc(fn ($r) => [$r['leads_worked'], $r['demo_ready']])
            ->values();
    }

    /**
     * Sales leaderboard sorted by conversions (with follow-up tally).
     */
    public function salesLeaderboard(Carbon $from, Carbon $to): Collection
    {
        return $this->salesTeam($from, $to)
            ->sortByDesc(fn ($r) => [$r['conversions'], $r['follow_ups']])
            ->values();
    }

    /**
     * Daily trend series for the last N days (distinct leads or event count).
     *
     * @return array<int, array{date: string, label: string, value: int}>
     */
    public function trend(?int $userId, array $types, int $days = 14, bool $distinctLeads = false): array
    {
        // Keyed by the current day so the rolling window rolls over cleanly.
        $key = 'analytics:trend:'.($userId ?? 'all').':'.implode(',', $types)
            .":{$days}:".($distinctLeads ? '1' : '0').':'.Carbon::today()->toDateString();

        return StatsCache::remember($key, fn () => $this->computeTrend($userId, $types, $days, $distinctLeads));
    }

    private function computeTrend(?int $userId, array $types, int $days, bool $distinctLeads): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $expr = $distinctLeads ? 'COUNT(DISTINCT lead_id)' : 'COUNT(*)';

        $rows = LeadEvent::query()
            ->whereIn('type', $types)
            ->where('created_at', '>=', $start)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->selectRaw("DATE(created_at) as d, {$expr} as aggregate")
            ->groupBy('d')
            ->pluck('aggregate', 'd');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = Carbon::today()->subDays($days - 1 - $i);
            $key = $day->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $day->format('M j'),
                'value' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Role-aware widget figures for the analytics dashboard header.
     *
     * @return array<int, array{label: string, value: int|string, tone: string}>
     */
    public function widgetsFor(User $user): array
    {
        return StatsCache::remember("analytics:widgets:{$user->role}:{$user->id}", fn () => $this->computeWidgetsFor($user));
    }

    private function computeWidgetsFor(User $user): array
    {
        if ($user->isDeveloper()) {
            [$monthFrom, $monthTo] = $this->resolveRange('month');

            return [
                ['label' => 'My Productivity (Leads Worked, Month)', 'value' => $this->leadsWorked($user->id, $monthFrom, $monthTo, self::DEV_EVENTS), 'tone' => 'indigo'],
                ['label' => 'Pending Leads', 'value' => Lead::where('developer_id', $user->id)
                    ->whereIn('workflow_status', [Lead::WF_ASSIGNED, Lead::WF_DEMO_IN_PROGRESS])->count(), 'tone' => 'amber'],
            ];
        }

        if ($user->hasRole(User::ROLE_SALES)) {
            [$monthFrom, $monthTo] = $this->resolveRange('month');
            $month = $this->typeCounts($user->id, $monthFrom, $monthTo);

            return [
                ['label' => 'Pending Follow Ups', 'value' => Lead::whereIn('workflow_status', [Lead::WF_DEMO_SENT, Lead::WF_FOLLOW_UP])->count(), 'tone' => 'amber'],
                ['label' => 'My Conversions (Month)', 'value' => $month[LeadEvent::TYPE_CONVERTED] ?? 0, 'tone' => 'green'],
            ];
        }

        // Admin / Leads Manager.
        [$monthFrom, $monthTo] = $this->resolveRange('month');
        $topDev = $this->developerLeaderboard($monthFrom, $monthTo)->first();
        $topSales = $this->salesLeaderboard($monthFrom, $monthTo)->first();

        $totalLeads = Lead::count();
        $converted = Lead::where('workflow_status', Lead::WF_CONVERTED)->count();
        $active = Lead::whereNotIn('workflow_status', [Lead::WF_CONVERTED, Lead::WF_REJECTED])->count();

        return [
            ['label' => 'Top Developer', 'value' => $topDev && $topDev['leads_worked'] > 0 ? $topDev['name'] : '—', 'tone' => 'indigo'],
            ['label' => 'Top Sales User', 'value' => $topSales && $topSales['conversions'] > 0 ? $topSales['name'] : '—', 'tone' => 'green'],
            ['label' => 'Conversion Rate', 'value' => $totalLeads > 0 ? round($converted / $totalLeads * 100).'%' : '0%', 'tone' => 'blue'],
            ['label' => 'Active Leads', 'value' => $active, 'tone' => 'amber'],
        ];
    }
}
