<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 8 — file-upload hardening and asset access control (IDOR).
 *
 * Verifies that developers can only reach assets on leads assigned to them,
 * that unsafe/invalid uploads are rejected, and that admins/sales are unscoped.
 */
class AssetSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => $role,
            'email' => $email,
            'password' => bcrypt('secret'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function lead(?int $developerId = null): Lead
    {
        return Lead::create([
            'business_name' => 'Acme',
            'status' => 'new',
            'website_exists' => false,
            'developer_id' => $developerId,
        ]);
    }

    private function asset(Lead $lead): LeadAsset
    {
        return $lead->assets()->create([
            'file_name' => 'doc.pdf',
            'file_path' => "lead-assets/{$lead->id}/doc.pdf",
            'file_type' => LeadAsset::TYPE_DOCUMENT,
        ]);
    }

    public function test_unassigned_developer_cannot_download_asset(): void
    {
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@example.com');
        $asset = $this->asset($this->lead()); // not assigned to $dev

        $this->actingAs($dev)
            ->get(route('assets.download', $asset))
            ->assertForbidden();
    }

    public function test_unassigned_developer_cannot_delete_asset(): void
    {
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@example.com');
        $asset = $this->asset($this->lead());

        $this->actingAs($dev)
            ->delete(route('assets.destroy', $asset))
            ->assertForbidden();

        $this->assertDatabaseHas('lead_assets', ['id' => $asset->id]);
    }

    public function test_unassigned_developer_cannot_upload_to_lead(): void
    {
        Storage::fake('public');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@example.com');
        $lead = $this->lead();

        $this->actingAs($dev)
            ->post(route('leads.assets.store', $lead), [
                'file_type' => LeadAsset::TYPE_DOCUMENT,
                'files' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->assertSame(0, $lead->assets()->count());
    }

    public function test_assigned_developer_can_upload(): void
    {
        Storage::fake('public');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@example.com');
        $lead = $this->lead($dev->id);

        $this->actingAs($dev)
            ->post(route('leads.assets.store', $lead), [
                'file_type' => LeadAsset::TYPE_DOCUMENT,
                'files' => [UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->assertSame(1, $lead->assets()->count());
    }

    public function test_svg_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'admin@example.com');
        $lead = $this->lead();

        $this->actingAs($admin)
            ->post(route('leads.assets.store', $lead), [
                'file_type' => LeadAsset::TYPE_IMAGE,
                'files' => [UploadedFile::fake()->create('x.svg', 4, 'image/svg+xml')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, $lead->assets()->count());
    }

    public function test_admin_can_download_any_asset(): void
    {
        Storage::fake('public');
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'admin@example.com');
        $lead = $this->lead();
        Storage::disk('public')->put("lead-assets/{$lead->id}/doc.pdf", 'data');
        $asset = $this->asset($lead);

        $this->actingAs($admin)
            ->get(route('assets.download', $asset))
            ->assertOk();
    }
}
