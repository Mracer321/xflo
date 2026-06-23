<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Notifications\FollowUpDueNotification;
use App\Notifications\LeadAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 7 notification-system audit. Drives the real HTTP routes via actingAs
 * so the controllers, FormRequests, role middleware and the database-notification
 * channel are all exercised end to end.
 */
class NotificationAuditTest extends TestCase
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

    private function lead(string $name = 'Acme'): Lead
    {
        return Lead::create([
            'business_name' => $name,
            'owner_name' => 'Jane '.$name,
            'status' => 'new',
            'website_exists' => false,
        ]);
    }

    private function log(string $line): void
    {
        fwrite(STDERR, "\n[AUDIT] {$line}");
    }

    // ===================================================================
    // 1. LEAD ASSIGNMENT NOTIFICATION
    // ===================================================================

    public function test_assignment_notifies_only_the_assigned_developer(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev1@x.test');
        $otherDev = $this->user(User::ROLE_DEVELOPER, 'dev2@x.test');
        $sales = $this->user(User::ROLE_SALES, 'sales@x.test');
        $lead = $this->lead();

        $this->actingAs($admin)
            ->post(route('leads.assign', $lead), ['developer_id' => $dev->id])
            ->assertRedirect();

        // Exactly one notification, owned by the assigned developer.
        $this->assertDatabaseCount('notifications', 1);
        $n = DatabaseNotification::first();
        $this->assertSame((string) $dev->id, (string) $n->notifiable_id);
        $this->assertSame(User::class, $n->notifiable_type);
        $this->assertSame(LeadAssignedNotification::class, $n->type);
        $this->assertNull($n->read_at, 'new notification must be unread');

        // Unrelated users receive nothing.
        $this->assertSame(1, $dev->fresh()->unreadNotifications->count());
        $this->assertSame(0, $otherDev->fresh()->unreadNotifications->count());
        $this->assertSame(0, $sales->fresh()->unreadNotifications->count());
        $this->assertSame(0, $admin->fresh()->unreadNotifications->count());

        $this->log('1. Assignment → only dev1 notified (dev1=1, dev2=0, sales=0, admin=0). PASS');
    }

    public function test_reassigning_the_same_developer_does_not_duplicate_notifications(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev1@x.test');
        $lead = $this->lead();

        // Assign twice to the SAME developer (no real change the second time).
        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);
        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);

        $count = $dev->fresh()->notifications->count();
        $this->log("1. Phase5 assign path, same dev twice → {$count} notification(s) (expected 1)");
        $this->assertSame(1, $count, 'assigning the same developer again must not create a duplicate');
    }

    public function test_developer_task_path_notifies_and_does_not_duplicate(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev1@x.test');
        $lead = $this->lead();

        $this->actingAs($admin)->post(route('leads.developer-task.store', $lead), ['developer_id' => $dev->id]);
        $this->actingAs($admin)->post(route('leads.developer-task.store', $lead), ['developer_id' => $dev->id]);

        $count = $dev->fresh()->notifications->count();
        $this->log("1. Developer-task path, same dev twice → {$count} notification(s) (expected 1)");
        $this->assertSame(1, $count);
    }

    public function test_self_assignment_guard_is_structurally_unreachable(): void
    {
        // The assignee must be role=developer, and only super_admin/leads_admin
        // may assign — so the actor can never be the assignee. A developer is
        // also forbidden from the assign routes. We confirm both gates here so
        // the "no self-notification" property holds by construction.
        $developer = $this->user(User::ROLE_DEVELOPER, 'dev@x.test');
        $lead = $this->lead();

        $this->actingAs($developer)
            ->post(route('leads.assign', $lead), ['developer_id' => $developer->id])
            ->assertForbidden();
        $this->actingAs($developer)
            ->post(route('leads.developer-task.store', $lead), ['developer_id' => $developer->id])
            ->assertForbidden();

        $this->assertDatabaseCount('notifications', 0);
        $this->log('1. Self-assignment unreachable (developer forbidden from both assign paths). PASS');
    }

    // ===================================================================
    // 2. FOLLOW-UP REMINDER NOTIFICATION
    // ===================================================================

    public function test_follow_up_reminder_goes_only_to_the_scheduler(): void
    {
        $sales = $this->user(User::ROLE_SALES, 'sales@x.test');
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $lead = $this->lead();

        // Sales schedules a follow-up due within a minute.
        $this->actingAs($sales)
            ->put(route('leads.follow-up.update', $lead), [
                'next_follow_up_at' => now()->addSeconds(30)->format('Y-m-d\TH:i:s'),
                'follow_up_notes' => 'Call the client back',
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame($sales->id, $lead->follow_up_user_id);
        $this->assertNull($lead->follow_up_notified_at, 'freshly scheduled → not yet notified');

        // Not due yet → command sends nothing.
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 0);
        $this->log('2. Before due time → 0 reminders. PASS');

        // Move past the due time, then run the command.
        $this->travel(2)->minutes();
        Artisan::call('leads:send-follow-up-reminders');

        $this->assertDatabaseCount('notifications', 1);
        $n = DatabaseNotification::first();
        $this->assertSame((string) $sales->id, (string) $n->notifiable_id);
        $this->assertSame(FollowUpDueNotification::class, $n->type);
        $this->assertSame(1, $sales->fresh()->unreadNotifications->count());
        $this->assertSame(0, $admin->fresh()->unreadNotifications->count(), 'Super Admin must NOT receive it');

        // notified_at is stamped to prevent re-notification.
        $this->assertNotNull($lead->fresh()->follow_up_notified_at);

        // Running again must NOT duplicate.
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 1);

        $this->travelBack();
        $this->log('2. Due → scheduler(sales)=1, admin=0; rerun stays at 1 (no duplicate). PASS');
    }

    public function test_rescheduling_rearms_the_reminder(): void
    {
        $sales = $this->user(User::ROLE_SALES, 'sales@x.test');
        $lead = $this->lead();

        $this->actingAs($sales)->put(route('leads.follow-up.update', $lead), [
            'next_follow_up_at' => now()->addSeconds(10)->format('Y-m-d\TH:i:s'),
            'follow_up_notes' => 'first',
        ]);
        $this->travel(1)->minutes();
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 1);
        $this->travelBack();

        // Reschedule → notified stamp cleared → fires again next run.
        $this->actingAs($sales)->put(route('leads.follow-up.update', $lead), [
            'next_follow_up_at' => now()->addSeconds(10)->format('Y-m-d\TH:i:s'),
            'follow_up_notes' => 'second',
        ]);
        $this->assertNull($lead->fresh()->follow_up_notified_at);
        $this->travel(1)->minutes();
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 2);
        $this->travelBack();

        $this->log('2. Rescheduling clears notified stamp and re-arms (1 → 2). PASS');
    }

    // ===================================================================
    // 3. NOTIFICATION CENTRE UI
    // ===================================================================

    public function test_notification_centre_unread_counts_and_mark_read(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@x.test');
        $lead = $this->lead();

        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);
        $this->assertSame(1, $dev->fresh()->unreadNotifications->count());

        // Page renders and shows the message.
        $this->actingAs($dev)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('assigned');

        // Mark one as read → unread drops to 0.
        $id = $dev->fresh()->notifications->first()->id;
        $this->actingAs($dev)->patch(route('notifications.read', $id))->assertRedirect();
        $this->assertSame(0, $dev->fresh()->unreadNotifications->count());
        $this->assertNotNull(DatabaseNotification::find($id)->read_at);

        // Add two more, then mark-all-read.
        $dev->notify(new LeadAssignedNotification($lead));
        $dev->notify(new LeadAssignedNotification($lead));
        $this->assertSame(2, $dev->fresh()->unreadNotifications->count());
        $this->actingAs($dev)->patch(route('notifications.read-all'))->assertRedirect();
        $this->assertSame(0, $dev->fresh()->unreadNotifications->count());

        $this->log('3. Unread 1→0 (read), 2→0 (read-all); index renders message. PASS');
    }

    public function test_user_cannot_read_another_users_notification(): void
    {
        $admin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@x.test');
        $intruder = $this->user(User::ROLE_DEVELOPER, 'dev2@x.test');
        $lead = $this->lead();

        $this->actingAs($admin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);
        $id = $dev->fresh()->notifications->first()->id;

        // Another user must not be able to mark dev's notification read.
        $this->actingAs($intruder)->patch(route('notifications.read', $id))->assertNotFound();
        $this->assertNull(DatabaseNotification::find($id)->read_at);

        $this->log('3. Cross-user read blocked (404, notification stays unread). PASS');
    }

    // ===================================================================
    // 4 & 5. ROLE MATRIX + DATABASE EVIDENCE DUMP
    // ===================================================================

    public function test_role_matrix_and_database_evidence(): void
    {
        $superAdmin = $this->user(User::ROLE_SUPER_ADMIN, 'sa@x.test');
        $leadsAdmin = $this->user(User::ROLE_LEADS_ADMIN, 'la@x.test');
        $sales = $this->user(User::ROLE_SALES, 'sales@x.test');
        $dev = $this->user(User::ROLE_DEVELOPER, 'dev@x.test');
        $lead = $this->lead('RoleMatrix');

        // Leads Admin assigns the developer → developer notified.
        $this->actingAs($leadsAdmin)->post(route('leads.assign', $lead), ['developer_id' => $dev->id]);

        // Sales schedules a follow-up → sales (the scheduler) notified when due.
        $this->actingAs($sales)->put(route('leads.follow-up.update', $lead), [
            'next_follow_up_at' => now()->addSeconds(10)->format('Y-m-d\TH:i:s'),
            'follow_up_notes' => 'Role matrix follow-up',
        ]);
        $this->travel(1)->minutes();
        Artisan::call('leads:send-follow-up-reminders');
        $this->travelBack();

        $matrix = [
            'super_admin' => $superAdmin->fresh()->notifications->count(),
            'leads_admin' => $leadsAdmin->fresh()->notifications->count(),
            'sales' => $sales->fresh()->notifications->count(),
            'developer' => $dev->fresh()->notifications->count(),
        ];

        // Expected: only the developer (assignment) and sales (scheduler) get one each.
        $this->assertSame(0, $matrix['super_admin']);
        $this->assertSame(0, $matrix['leads_admin']);
        $this->assertSame(1, $matrix['sales']);
        $this->assertSame(1, $matrix['developer']);

        $this->log('4. ROLE MATRIX (notifications received):');
        foreach ($matrix as $role => $count) {
            $this->log(sprintf('     %-12s %d', $role, $count));
        }

        // ---- 5. Database evidence dump ----
        $rows = DB::table('notifications')
            ->select('id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at')
            ->get();

        $this->log('5. notifications TABLE ('.$rows->count().' rows):');
        foreach ($rows as $r) {
            $data = json_decode($r->data, true);
            $this->log(sprintf(
                '     notifiable_id=%s type=%s read_at=%s msg="%s"',
                $r->notifiable_id,
                class_basename($r->type),
                $r->read_at ?? 'NULL(unread)',
                $data['message'] ?? '',
            ));
        }
    }

    // ===================================================================
    // 6. TIMEZONE VERIFICATION
    // ===================================================================

    public function test_timezone_configuration_and_due_comparison(): void
    {
        $tz = config('app.timezone');
        $this->log("6. config('app.timezone') = {$tz}");
        $this->log('6. now() = '.now()->toDateTimeString().' ('.now()->tzName.')');

        $sales = $this->user(User::ROLE_SALES, 'sales@x.test');
        $lead = $this->lead();

        // Schedule 30 min in the future (app frame).
        $this->actingAs($sales)->put(route('leads.follow-up.update', $lead), [
            'next_follow_up_at' => now()->addMinutes(30)->format('Y-m-d\TH:i:s'),
            'follow_up_notes' => 'tz check',
        ]);

        // Stored value and now() are in the SAME frame → not due yet.
        $this->assertTrue($lead->fresh()->next_follow_up_at->isFuture());
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 0);

        // Travel just past it → becomes due, comparison fires correctly.
        $this->travel(31)->minutes();
        Artisan::call('leads:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 1);
        $this->travelBack();

        $this->log('6. Due comparison consistent: future→0, after travel→1. PASS');
    }
}
