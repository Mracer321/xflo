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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Core details
            $table->string('business_name');
            $table->string('owner_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();
            $table->string('category')->nullable();
            $table->text('address')->nullable();

            // Web presence
            $table->string('google_business_url')->nullable();
            $table->boolean('website_exists')->default(false);
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();

            // Pipeline
            $table->string('status')->default('new')->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Helps search/sort on the most-used columns.
            $table->index('business_name');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
