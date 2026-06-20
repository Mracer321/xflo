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
            // Phase 5 demo workflow status (separate from the legacy `status` column).
            // new_lead | assigned | demo_in_progress | demo_ready | demo_sent | follow_up | converted | rejected
            $table->string('workflow_status')->default('new_lead')->index()->after('status');

            // Assigned developer for the demo workflow.
            $table->foreignId('developer_id')->nullable()->after('workflow_status')
                ->constrained('users')->nullOnDelete();

            // Demo website information.
            $table->string('demo_url')->nullable()->after('developer_id');
            $table->timestamp('demo_created_at')->nullable()->after('demo_url');
            $table->timestamp('demo_sent_at')->nullable()->after('demo_created_at');
            $table->text('demo_notes')->nullable()->after('demo_sent_at');   // developer notes
            $table->text('sales_notes')->nullable()->after('demo_notes');    // sales notes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['developer_id']);
            $table->dropColumn([
                'workflow_status',
                'developer_id',
                'demo_url',
                'demo_created_at',
                'demo_sent_at',
                'demo_notes',
                'sales_notes',
            ]);
        });
    }
};
