<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_custody_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evidence_id')->constrained('evidence')->restrictOnDelete();
            $table->string('event_type', 30);
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['evidence_id', 'occurred_at'], 'evidence_custody_logs_evidence_id_occurred_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_custody_logs');
    }
};
