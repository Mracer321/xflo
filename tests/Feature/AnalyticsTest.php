<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 6 — Analytics & Productivity Dashboard.
 *
 * Verifies daily/weekly/monthly aggregation, role-based visibility, leaderboards,
 * team tables, chart series and query efficiency — all derived from lead_events.
 */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst(str_replace('_', ' ', $role)), 'email' => $email,
            'password' => bcrypt('secret'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function lead(array $attrs = []): Lead
    {
        return Lead::create(array_merge(['business_name' => 'Acme', 'status' => 'new', 'website_exists' => false], $attrs));
    }

    /** Create a lead_event at a specific time for a specific actor. */
    private function event(Lead $lead, User $actor, string $type, Carbon $at): void
    {
        $e = $lead->events()->create(['user_id' => $actor->id, 'type' => $type]);
        $e->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
    }

    public function test_developer_daily_weekly_monthly_metrics(): void
    {
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');
        $lead = $this->lead(['developer_id' => $dev->id]);

        // Today: two events on the SAME lead (started + ready) → 1 distinct lead worked.
        $this->event($lead, $dev, LeadEvent::TYPE_DEMO_STARTED, Carbon::today()->setTime(9, 0));
        $this->event($lead, $dev, LeadEvent::TYPE_DEMO_READY, Carbon::today()->setTime(15, 0));

        $m = app(AnalyticsService::class)->developerMetrics($dev->id);

        // Distinct-lead semantics: same lead worked twice today counts once.
        $this->assertSame(1, $m['today']['Leads Worked']);
        $this->assertSame(1, $m['today']['Demo In Progress']);
        $this->assertSame(1, $m['today']['Demo Ready']);
        $this->assertSame(1, $m['today']['Demos Completed']);
        // Today is within this week and this month, so the single ready event rolls up.
        $this->assertSame(1, $m['week']['Demos Completed']);
        $this->assertSame(1, $m['month']['Demos Completed']);
    }

    public function test_sales_metrics_count_by_type(): void
    {
        $sales = $this->make(User::ROLE_SALES, 'sales@x.com');
        $lead = $this->lead();

        $this->event($lead, $sales, LeadEvent::TYPE_DEMO_SENT, Carbon::today()->setTime(9, 0));
        $this->event($lead, $sales, LeadEvent::TYPE_FOLLOW_UP, Carbon::today()->setTime(10, 0));
        $this->event($lead, $sales, LeadEvent::TYPE_CONVERTED, Carbon::today()->setTime(11, 0));

        $m = app(AnalyticsService::class)->salesMetrics($sales->id);
        $this->assertSame(1, $m['today']['Demo Sent']);
        $this->assertSame(1, $m['today']['Follow Ups Done']);
        $this->assertSame(1, $m['today']['Converted Leads']);
        $this->assertSame(0, $m['today']['Rejected Leads']);
        $this->assertSame(1, $m['month']['Conversions']);
    }

    public function test_admin_monthly_conversion_rate(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $l1 = $this->lead();
        $l2 = $this->lead(['business_name' => 'B']);
        // 2 created, 1 converted this month → 50%.
        $this->event($l1, $admin, LeadEvent::TYPE_CREATED, Carbon::now()->startOfMonth()->addDay());
        $this->event($l2, $admin, LeadEvent::TYPE_CREATED, Carbon::now()->startOfMonth()->addDays(2));
        $this->event($l1, $admin, LeadEvent::TYPE_CONVERTED, Carbon::now()->startOfMonth()->addDays(3));

        $m = app(AnalyticsService::class)->adminMetrics();
        $this->assertSame(2, $m['month']['Total Leads']);
        $this->assertSame(1, $m['month']['Total Conversions']);
        $this->assertSame('50%', $m['month']['Conversion Rate']);
    }

    public function test_leaderboards_rank_correctly(): void
    {
        $dev1 = $this->make(User::ROLE_DEVELOPER, 'd1@x.com');
        $dev2 = $this->make(User::ROLE_DEVELOPER, 'd2@x.com');
        $lead = $this->lead();
        // dev2 works two distinct leads, dev1 one.
        $this->event($lead, $dev1, LeadEvent::TYPE_DEMO_READY, Carbon::now()->subDay());
        $lb = $this->lead(['business_name' => 'B']);
        $lc = $this->lead(['business_name' => 'C']);
        $this->event($lb, $dev2, LeadEvent::TYPE_DEMO_READY, Carbon::now()->subDay());
        $this->event($lc, $dev2, LeadEvent::TYPE_DEMO_STARTED, Carbon::now()->subDay());

        [$from, $to] = app(AnalyticsService::class)->resolveRange('month');
        $board = app(AnalyticsService::class)->developerLeaderboard($from, $to);
        $this->assertSame($dev2->name, $board->first()['name']);
        $this->assertSame(2, $board->first()['leads_worked']);
    }

    public function test_trend_series_has_one_point_per_day(): void
    {
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');
        $lead = $this->lead();
        $this->event($lead, $dev, LeadEvent::TYPE_DEMO_READY, Carbon::today());

        $series = app(AnalyticsService::class)->trend($dev->id, ['demo_ready'], 14);
        $this->assertCount(14, $series);
        $this->assertSame(1, $series[13]['value']); // today is the last point
        $this->assertSame(0, $series[0]['value']);
    }

    public function test_role_based_page_visibility(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $manager = $this->make(User::ROLE_LEADS_ADMIN, 'mgr@x.com');
        $sales = $this->make(User::ROLE_SALES, 'sales@x.com');
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');

        // All roles can see their own analytics dashboard.
        foreach ([$admin, $manager, $sales, $dev] as $u) {
            $this->actingAs($u)->get(route('analytics.index'))->assertOk();
        }

        // Team page: admins + managers only.
        $this->actingAs($admin)->get(route('analytics.team'))->assertOk();
        $this->actingAs($manager)->get(route('analytics.team'))->assertOk();
        $this->actingAs($sales)->get(route('analytics.team'))->assertForbidden();
        $this->actingAs($dev)->get(route('analytics.team'))->assertForbidden();
    }

    public function test_admin_dashboard_shows_leaderboards_but_developer_does_not(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');

        $adminHtml = $this->actingAs($admin)->get(route('analytics.index'))->getContent();
        $this->assertStringContainsString('Developer Leaderboard', $adminHtml);
        $this->assertStringContainsString('Team Performance', $adminHtml);

        $devHtml = $this->actingAs($dev)->get(route('analytics.index'))->getContent();
        $this->assertStringNotContainsString('Developer Leaderboard', $devHtml);
        $this->assertStringContainsString('My Productivity', $devHtml);
    }
}
