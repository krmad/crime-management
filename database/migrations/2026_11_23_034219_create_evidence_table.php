<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->string('evidence_number', 40)->unique('evidence_evidence_number_unique');
            $table->string('title');
            $table->string('evidence_type', 30);
            $table->text('description')->nullable();
            $table->dateTime('collected_at');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('storage_location', 255)->nullable();
            $table->string('status', 30)->default('collected');
            $table->string('private_file_path', 512)->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->index(['case_id', 'created_at'], 'evidence_case_id_created_at_index');
            $table->index(['status', 'collected_at'], 'evidence_status_collected_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence');
    }
};
