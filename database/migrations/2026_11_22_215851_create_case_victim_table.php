<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_victim', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('victim_id')->constrained('victims')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['case_id', 'victim_id'], 'case_victim_case_id_victim_id_unique');
            $table->index(['victim_id', 'case_id'], 'case_victim_victim_id_case_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_victim');
    }
};
