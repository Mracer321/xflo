<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 8 — composite indexes for the analytics & listing hot paths.
     *
     * `lead_events` is the analytics hot table: every figure is a GROUP BY over
     * (user_id, type) filtered by created_at, or a team-wide (type) filtered by
     * created_at. It previously carried only a single-column `type` index. The
     * `leads` indexes cover the date-range filters / latest() ordering on the
     * list page and the developer dashboard counts.
     *
     * All additive (no data change); index names are explicit so down() is exact.
     * Portable across MySQL and SQLite (the test connection).
     */
    public function up(): void
    {
        Schema::table('lead_events', function (Blueprint $table) {
            // Per-actor productivity: perUserTypeCounts / perUserLeadsWorked /
            // typeCounts(userId) — WHERE user_id = ? AND type IN (...) AND created_at BETWEEN.
            $table->index(['user_id', 'type', 'created_at'], 'lead_events_user_type_created_idx');

            // Team-wide aggregates & trends: typeCounts(null) / trend(null) —
            // WHERE type IN (...) AND created_at >= ?.
            $table->index(['type', 'created_at'], 'lead_events_type_created_idx');

            // "Created By" filter (whereHas events type=created) and per-lead
            // timeline lookups — WHERE lead_id = ? AND type = ?.
            $table->index(['lead_id', 'type'], 'lead_events_lead_type_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            // Date-range filters (whereDate created_at) + default latest() ordering.
            $table->index('created_at', 'leads_created_at_idx');

            // Developer dashboard: WHERE developer_id = ? AND workflow_status = ?.
            $table->index(['developer_id', 'workflow_status'], 'leads_developer_workflow_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lead_events', function (Blueprint $table) {
            $table->dropIndex('lead_events_user_type_created_idx');
            $table->dropIndex('lead_events_type_created_idx');
            $table->dropIndex('lead_events_lead_type_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_created_at_idx');
            $table->dropIndex('leads_developer_workflow_idx');
        });
    }
};
