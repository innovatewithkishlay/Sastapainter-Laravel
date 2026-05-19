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
        Schema::create('site_visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone');
            $table->enum('city', ['Bangalore', 'Delhi', 'Noida', 'Mumbai', 'Pune', 'Hyderabad', 'Chennai']);
            $table->enum('status', ['Pending', 'Confirmed', 'Scheduled', 'Inspection_Done', 'Completed', 'Cancelled'])->default('Pending');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
