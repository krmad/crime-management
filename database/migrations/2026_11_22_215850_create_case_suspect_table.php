<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_suspect', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('suspect_id')->constrained('suspects')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('identified');
            $table->timestamps();

            $table->unique(['case_id', 'suspect_id'], 'case_suspect_case_id_suspect_id_unique');
            $table->index(['suspect_id', 'case_id'], 'case_suspect_suspect_id_case_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_suspect');
    }
};
