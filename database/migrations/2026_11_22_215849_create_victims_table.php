<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('victims', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();

            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('victims');
    }
};
