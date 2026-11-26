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
        Schema::create('case_judgments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('judge_id')->constrained('users')->restrictOnDelete();
            $table->string('judgment_state', 20)->default('draft');
            $table->string('verdict', 30)->nullable();
            $table->text('judgment_notes')->nullable();
            $table->text('acquittal_notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['case_id', 'judgment_state'], 'case_judgments_case_id_judgment_state_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_judgments');
    }
};
