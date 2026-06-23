<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Notifications\FollowUpDueNotification;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:send-follow-up-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify scheduling users of leads whose follow-up is now due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leads = Lead::followUpDue()->with('followUpUser')->get();

        $sent = 0;

        foreach ($leads as $lead) {
            // Skip leads whose scheduling user has since been removed; stamp
            // them so we don't re-scan them every run.
            if ($lead->followUpUser) {
                $lead->followUpUser->notify(new FollowUpDueNotification($lead));
                $sent++;
            }

            $lead->forceFill(['follow_up_notified_at' => now()])->save();
        }

        $this->info("Follow-up reminders sent: {$sent} (of {$leads->count()} due).");

        return self::SUCCESS;
    }
}
