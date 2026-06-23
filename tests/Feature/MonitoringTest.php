<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 8 — monitoring endpoints and the admin-only status page.
 */
class MonitoringTest extends TestCase
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

    public function test_health_probe_is_public_and_returns_json(): void
    {
        $this->get('/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'checks' => ['database', 'cache', 'queue', 'storage']])
            ->assertJson(['status' => 'ok']);
    }

    public function test_status_page_forbidden_for_non_super_admin(): void
    {
        foreach ([User::ROLE_LEADS_ADMIN, User::ROLE_SALES, User::ROLE_DEVELOPER] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('system.status'))
                ->assertForbidden();
        }
    }

    public function test_status_page_visible_to_super_admin(): void
    {
        $this->actingAs($this->user(User::ROLE_SUPER_ADMIN))
            ->get(route('system.status'))
            ->assertOk()
            ->assertSee('System Status');
    }

    public function test_custom_404_page_renders(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');
    }
}
