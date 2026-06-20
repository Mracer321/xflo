<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Demo website lifecycle, tracked separately from the sales workflow_status.
            // live | offline | deleted
            $table->string('demo_status')->default('live')->index()->after('sales_notes');

            $table->text('offline_reason')->nullable()->after('demo_status');
            $table->timestamp('offline_at')->nullable()->after('offline_reason');
            // Timestamp the demo record was (soft) marked deleted — distinct from the row being removed.
            $table->timestamp('deleted_at_demo')->nullable()->after('offline_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'demo_status',
                'offline_reason',
                'offline_at',
                'deleted_at_demo',
            ]);
        });
    }
};
