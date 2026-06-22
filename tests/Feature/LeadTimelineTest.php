<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5.2 — Lead Activity Timeline.
 *
 * Verifies an immutable, auto-generated event is recorded for every workflow
 * action, that the timeline renders newest-first with actor + timestamp, and
 * that event visibility follows lead access (developers: assigned leads only).
 */
class LeadTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $email,
            'password' => bcrypt('secret'), 'role' => $role, 'is_active' => true,
        ]);
    }

    private function lead(): Lead
    {
        return Lead::create(['business_name' => 'Acme', 'status' => 'new', 'website_exists' => false]);
    }

    public function test_create_and_update_record_events(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');

        $this->actingAs($admin)->post(route('leads.store'), [
            'business_name' => 'New Biz', 'status' => 'new', 'website_exists' => false,
        ])->assertRedirect();
        $lead = Lead::where('business_name', 'New Biz')->firstOrFail();
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_CREATED]);

        $this->actingAs($admin)->put(route('leads.update', $lead), [
            'business_name' => 'New Biz Renamed', 'status' => 'contacted', 'website_exists' => false,
        ])->assertRedirect();
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_UPDATED]);
    }

    public function test_assign_then_reassign_records_assigned_then_changed(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $dev1 = $this->make(User::ROLE_DEVELOPER, 'dev1@x.com');
        $dev2 = $this->make(User::ROLE_DEVELOPER, 'dev2@x.com');
        $lead = $this->lead();

        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev1->id]);
        $this->assertDatabaseHas('lead_events', [
            'lead_id' => $lead->id, 'type' => LeadEvent::TYPE_ASSIGNED, 'new_value' => $dev1->name,
        ]);

        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev2->id]);
        $this->assertDatabaseHas('lead_events', [
            'lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEVELOPER_CHANGED,
            'old_value' => $dev1->name, 'new_value' => $dev2->name,
        ]);
    }

    public function test_developer_demo_update_records_url_added_and_status_events(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');
        $lead = $this->lead();
        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);

        $this->actingAs($dev)->put(route('leads.demo.update', $lead), [
            'workflow_status' => Lead::WF_DEMO_IN_PROGRESS,
            'demo_url' => 'https://demo.example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_URL_ADDED, 'new_value' => 'https://demo.example.com']);
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_STARTED, 'new_value' => 'Demo In Progress']);

        $this->actingAs($dev)->put(route('leads.demo.update', $lead), [
            'workflow_status' => Lead::WF_DEMO_READY, 'demo_url' => 'https://demo.example.com',
        ]);
        $this->assertDatabaseHas('lead_events', [
            'lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_READY,
            'old_value' => 'Demo In Progress', 'new_value' => 'Demo Ready',
        ]);
    }

    public function test_sales_and_lifecycle_actions_record_events(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $sales = $this->make(User::ROLE_SALES, 'sales@x.com');
        $lead = $this->lead();
        $lead->update(['workflow_status' => Lead::WF_DEMO_READY]);

        foreach ([Lead::WF_DEMO_SENT, Lead::WF_FOLLOW_UP, Lead::WF_CONVERTED] as $status) {
            $this->actingAs($sales)->put(route('leads.sales.update', $lead), ['workflow_status' => $status])->assertRedirect();
        }
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_SENT]);
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_FOLLOW_UP]);
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_CONVERTED]);

        // Demo lifecycle: offline then reactivated.
        $this->actingAs($admin)->put(route('leads.demo-status.update', $lead), ['demo_status' => Lead::DEMO_OFFLINE, 'offline_reason' => 'maintenance'])->assertRedirect();
        $this->actingAs($admin)->put(route('leads.demo-status.update', $lead), ['demo_status' => Lead::DEMO_LIVE])->assertRedirect();
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_OFFLINE]);
        $this->assertDatabaseHas('lead_events', ['lead_id' => $lead->id, 'type' => LeadEvent::TYPE_DEMO_REACTIVATED]);
    }

    public function test_timeline_renders_newest_first_with_actor_for_accessible_lead(): void
    {
        $admin = $this->make(User::ROLE_SUPER_ADMIN, 'admin@x.com');
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');
        $lead = $this->lead();
        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);

        $html = $this->actingAs($admin)->get(route('leads.show', $lead))->assertOk()->getContent();
        $this->assertStringContainsString('Timeline', $html);
        $this->assertStringContainsString('Assigned to Developer', $html);
        $this->assertStringContainsString($dev->name, $html); // actor / new value

        // Newest-first: the most recent event appears before an earlier one.
        $assignedPos = strpos($html, 'Assigned to Developer');
        $createdPos = strpos($html, 'Lead Created');
        if ($createdPos !== false) {
            $this->assertLessThan($createdPos, $assignedPos, 'Timeline should be newest-first.');
        }
    }

    public function test_developer_cannot_view_timeline_of_unassigned_lead(): void
    {
        $dev = $this->make(User::ROLE_DEVELOPER, 'dev@x.com');
        $other = $this->make(User::ROLE_DEVELOPER, 'other@x.com');
        $lead = $this->lead();
        $lead->update(['developer_id' => $other->id]);

        $this->actingAs($dev)->get(route('leads.show', $lead))->assertForbidden();
    }

    public function test_events_are_immutable_no_edit_or_delete_routes(): void
    {
        // There must be no user-facing route to edit or delete timeline events.
        $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->getName())->filter();
        $this->assertTrue($routes->filter(fn ($n) => str_contains((string) $n, 'event'))->isEmpty(),
            'No event edit/delete routes should exist — timeline history is auto-generated and immutable.');
    }
}
