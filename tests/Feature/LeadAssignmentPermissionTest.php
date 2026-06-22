<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the lead-assignment permission matrix:
 *
 *   Super Admin / Leads Manager → may assign & reassign developers
 *   Sales / Developer           → may NOT assign developers
 *
 * Covers both assignment paths (Phase 5 `leads.assign` and Phase 3
 * `leads.developer-task.store`) and confirms Super Admin is unrestricted.
 */
class LeadAssignmentPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role): User
    {
        return User::create([
            'name' => $role,
            'email' => "{$role}@example.com",
            'password' => bcrypt('secret'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function developer(): User
    {
        return User::create([
            'name' => 'Dev',
            'email' => 'dev@example.com',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_DEVELOPER,
            'is_active' => true,
        ]);
    }

    private function lead(): Lead
    {
        return Lead::create(['business_name' => 'Acme', 'status' => 'new', 'website_exists' => false]);
    }

    public function test_super_admin_can_assign_and_reassign_developer(): void
    {
        $admin = $this->actor(User::ROLE_SUPER_ADMIN);
        $dev1 = $this->developer();
        $dev2 = User::create(['name' => 'Dev2', 'email' => 'dev2@example.com', 'password' => bcrypt('secret'), 'role' => User::ROLE_DEVELOPER, 'is_active' => true]);
        $lead = $this->lead();

        $this->actingAs($admin)
            ->post(route('leads.assign', $lead), ['developer_id' => $dev1->id])
            ->assertRedirect();
        $this->assertSame($dev1->id, $lead->fresh()->developer_id);

        // Reassign.
        $this->actingAs($admin)
            ->post(route('leads.assign', $lead), ['developer_id' => $dev2->id])
            ->assertRedirect();
        $this->assertSame($dev2->id, $lead->fresh()->developer_id);
    }

    public function test_super_admin_can_assign_via_developer_task_path(): void
    {
        $admin = $this->actor(User::ROLE_SUPER_ADMIN);
        $dev = $this->developer();
        $lead = $this->lead();

        $this->actingAs($admin)
            ->post(route('leads.developer-task.store', $lead), ['developer_id' => $dev->id])
            ->assertRedirect();

        $this->assertSame($dev->id, $lead->fresh()->developerTask->developer_id);
    }

    public function test_leads_manager_can_assign_developer(): void
    {
        $manager = $this->actor(User::ROLE_LEADS_ADMIN);
        $dev = $this->developer();
        $lead = $this->lead();

        $this->actingAs($manager)
            ->post(route('leads.assign', $lead), ['developer_id' => $dev->id])
            ->assertRedirect();

        $this->assertSame($dev->id, $lead->fresh()->developer_id);
    }

    public function test_sales_cannot_assign_developer_via_either_path(): void
    {
        $sales = $this->actor(User::ROLE_SALES);
        $dev = $this->developer();
        $lead = $this->lead();

        $this->actingAs($sales)->post(route('leads.assign', $lead), ['developer_id' => $dev->id])->assertForbidden();
        $this->actingAs($sales)->post(route('leads.developer-task.store', $lead), ['developer_id' => $dev->id])->assertForbidden();

        $this->assertNull($lead->fresh()->developer_id);
        $this->assertNull($lead->fresh()->developerTask);
    }

    public function test_developer_cannot_assign_developer(): void
    {
        $developer = $this->actor(User::ROLE_DEVELOPER);
        $lead = $this->lead();

        $this->actingAs($developer)->post(route('leads.assign', $lead), ['developer_id' => $developer->id])->assertForbidden();

        $this->assertNull($lead->fresh()->developer_id);
    }
}
