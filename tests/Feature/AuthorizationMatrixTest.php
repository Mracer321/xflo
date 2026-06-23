<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 — cross-role authorization matrix (direct-URL access defense).
 *
 * Complements LeadAssignmentPermissionTest by checking user-management,
 * developer-only and sales-only routes plus the unauthenticated redirect.
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => $role,
            'email' => "{$role}@example.com",
            'password' => bcrypt('secret'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function lead(): Lead
    {
        return Lead::create(['business_name' => 'Acme', 'status' => 'new', 'website_exists' => false]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('leads.index'))->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_reach_user_management(): void
    {
        foreach ([User::ROLE_LEADS_ADMIN, User::ROLE_SALES, User::ROLE_DEVELOPER] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('users.index'))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_reach_user_management(): void
    {
        $this->actingAs($this->user(User::ROLE_SUPER_ADMIN))
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_sales_cannot_post_developer_demo_update(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->user(User::ROLE_SALES))
            ->put(route('leads.demo.update', $lead), ['workflow_status' => Lead::WF_DEMO_IN_PROGRESS])
            ->assertForbidden();
    }

    public function test_developer_cannot_post_sales_update(): void
    {
        $lead = $this->lead();

        $this->actingAs($this->user(User::ROLE_DEVELOPER))
            ->put(route('leads.sales.update', $lead), ['workflow_status' => Lead::WF_DEMO_SENT])
            ->assertForbidden();
    }
}
