<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 7 — follow-up scheduling fields on leads.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('next_follow_up_at')->nullable()->index()->after('sales_notes');
            $table->text('follow_up_notes')->nullable()->after('next_follow_up_at');
            // Marks the moment a "due" notification/missed event was emitted, to
            // avoid re-notifying for the same scheduled follow-up.
            $table->timestamp('follow_up_notified_at')->nullable()->after('follow_up_notes');
            // The user who scheduled the follow-up; the reminder is sent only to
            // them. Nulled (not cascaded) if that user is later removed.
            $table->foreignId('follow_up_user_id')->nullable()->after('follow_up_notified_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_user_id');
            $table->dropColumn(['next_follow_up_at', 'follow_up_notes', 'follow_up_notified_at']);
        });
    }
};
