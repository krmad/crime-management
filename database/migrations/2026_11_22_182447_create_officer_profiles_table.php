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
        Schema::create('officer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('badge_number')->nullable()->unique();
            $table->string('rank')->nullable();
            $table->string('department')->nullable();
            $table->string('police_station')->nullable();
            $table->string('phone')->nullable();
            $table->string('employment_status')->default('active');
            $table->timestamps();

            $table->index('rank');
            $table->index('employment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_profiles');
    }
};
