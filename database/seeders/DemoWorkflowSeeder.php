<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoWorkflowSeeder extends Seeder
{
    /**
     * Seed sample leads spread across every workflow stage, with timeline events.
     */
    public function run(): void
    {
        $dev1 = User::where('email', 'developer@xflow.com')->first();
        $dev2 = User::where('email', 'dana@xflow.com')->first();
        $admin = User::where('email', 'admin@xflow.com')->first();
        $sales = User::where('email', 'sales@xflow.com')->first();

        $now = Carbon::now();

        $samples = [
            [
                'business_name' => 'Demo Cafe',
                'workflow_status' => Lead::WF_NEW_LEAD,
                'developer_id' => null,
                'events' => [['created', 'Lead created.', $admin]],
            ],
            [
                'business_name' => 'Bloom Florist',
                'workflow_status' => Lead::WF_ASSIGNED,
                'developer_id' => $dev1?->id,
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['assigned', "Assigned to {$dev1?->name}.", $admin],
                ],
            ],
            [
                'business_name' => 'Pixel Studio',
                'workflow_status' => Lead::WF_DEMO_IN_PROGRESS,
                'developer_id' => $dev2?->id,
                'demo_notes' => 'Building landing page with booking form.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['assigned', "Assigned to {$dev2?->name}.", $admin],
                    ['demo_started', 'Demo development started.', $dev2],
                ],
            ],
            [
                'business_name' => 'Green Grocer',
                'workflow_status' => Lead::WF_DEMO_READY,
                'developer_id' => $dev2?->id,
                'demo_url' => 'https://green-grocer-demo.vercel.app',
                'demo_created_at' => $now,
                'demo_notes' => 'Demo ready for review.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['assigned', "Assigned to {$dev2?->name}.", $admin],
                    ['demo_started', 'Demo development started.', $dev2],
                    ['demo_ready', 'Demo marked ready.', $dev2],
                ],
            ],
            [
                'business_name' => 'Urban Fitness',
                'workflow_status' => Lead::WF_DEMO_SENT,
                'developer_id' => $dev1?->id,
                'demo_url' => 'https://urban-fitness-demo.netlify.app',
                'demo_created_at' => $now->copy()->subDays(2),
                'demo_sent_at' => $now->copy()->subDay(),
                'sales_notes' => 'Sent demo link to owner via WhatsApp.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['assigned', "Assigned to {$dev1?->name}.", $admin],
                    ['demo_ready', 'Demo marked ready.', $dev1],
                    ['demo_sent', 'Status updated to Demo Sent.', $sales],
                ],
            ],
            [
                'business_name' => 'Sky Travels',
                'workflow_status' => Lead::WF_FOLLOW_UP,
                'developer_id' => $dev1?->id,
                'demo_sent_at' => $now->copy()->subDays(3),
                'sales_notes' => 'Following up — owner asked for pricing.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['demo_sent', 'Status updated to Demo Sent.', $sales],
                    ['follow_up', 'Status updated to Follow Up.', $sales],
                ],
            ],
            [
                'business_name' => 'Metro Motors',
                'workflow_status' => Lead::WF_CONVERTED,
                'developer_id' => $dev2?->id,
                'sales_notes' => 'Closed — signed annual plan.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['demo_sent', 'Status updated to Demo Sent.', $sales],
                    ['converted', 'Status updated to Converted.', $sales],
                ],
            ],
            [
                'business_name' => 'Old Diner',
                'workflow_status' => Lead::WF_REJECTED,
                'developer_id' => $dev1?->id,
                'sales_notes' => 'Not interested — already has a website.',
                'events' => [
                    ['created', 'Lead created.', $admin],
                    ['rejected', 'Status updated to Rejected.', $sales],
                ],
            ],
        ];

        foreach ($samples as $sample) {
            $events = $sample['events'];
            unset($sample['events']);

            $lead = Lead::updateOrCreate(
                ['business_name' => $sample['business_name']],
                array_merge([
                    'status' => Lead::STATUS_NEW,
                    'website_exists' => false,
                ], $sample),
            );

            // Rebuild timeline cleanly on re-run.
            $lead->events()->delete();

            $minutes = 0;
            foreach ($events as [$type, $description, $actor]) {
                $lead->events()->create([
                    'user_id'     => $actor?->id,
                    'type'        => $type,
                    'description' => $description,
                    'created_at'  => $now->copy()->addMinutes($minutes),
                    'updated_at'  => $now->copy()->addMinutes($minutes),
                ]);
                $minutes += 5;
            }
        }
    }
}
