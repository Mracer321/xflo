<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 5.2: capture the before/after value for a transition (e.g. workflow
     * status or assigned developer) so the timeline can show "X → Y" and Phase 6
     * analytics can reason about state changes. The existing `description` column
     * continues to hold the optional human-readable notes.
     */
    public function up(): void
    {
        Schema::table('lead_events', function (Blueprint $table) {
            $table->text('old_value')->nullable()->after('description');
            $table->text('new_value')->nullable()->after('old_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_events', function (Blueprint $table) {
            $table->dropColumn(['old_value', 'new_value']);
        });
    }
};
