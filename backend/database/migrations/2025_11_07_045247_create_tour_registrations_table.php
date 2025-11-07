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
        Schema::create('tour_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('organisation')->nullable();
            $table->string('group_type')->default('individual');
            $table->date('preferred_date');
            $table->string('preferred_slot');
            $table->unsignedInteger('adults_count')->default(0);
            $table->unsignedInteger('students_count')->default(0);
            $table->boolean('needs_guided_tour')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_registrations');
    }
};
