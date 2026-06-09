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
        Schema::create('developer_tasks', function (Blueprint $table) {
            $table->id();

            // One workflow record per lead.
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();

            // Assigned developer (nullable so a lead can be created before assignment).
            $table->foreignId('developer_id')->nullable()->constrained('users')->nullOnDelete();

            // Developer workflow status: not_started | developing | deploying | demo_ready | offline | deleted
            $table->string('status')->default('not_started')->index();

            $table->text('notes')->nullable();
            $table->string('demo_url')->nullable();
            // vercel | netlify | cloudflare_pages
            $table->string('deployment_platform')->nullable();
            $table->date('deployment_date')->nullable();
            // Mandatory when status is offline or deleted.
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('developer_tasks');
    }
};
