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
        Schema::create('lead_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // Actor who triggered the event (nullable for system/seed entries).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Event type: created | assigned | demo_started | demo_ready | demo_sent | follow_up | converted | rejected | note
            $table->string('type')->index();
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_events');
    }
};
