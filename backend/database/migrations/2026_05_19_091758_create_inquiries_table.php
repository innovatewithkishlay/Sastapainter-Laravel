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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->enum('city', ['Delhi', 'Noida']);
            $table->string('service_type');
            $table->text('message')->nullable();
            $table->text('address');
            $table->string('pincode');
            $table->dateTime('preferred_date')->nullable();
            $table->enum('status', ['Pending', 'Contacted', 'Scheduled', 'In_Progress', 'Inspection_Done', 'Completed', 'Cancelled'])->default('Pending');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('assignedPainter')->nullable()->constrained('painters')->onDelete('set null');
            $table->json('editHistory')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
