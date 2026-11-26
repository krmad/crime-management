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
        Schema::create('punishments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_judgment_id')->constrained('case_judgments')->restrictOnDelete();
            $table->string('type', 30);
            $table->decimal('fine_amount', 15, 2)->nullable();
            $table->string('imprisonment_term', 100)->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['case_judgment_id', 'type'], 'punishments_case_judgment_id_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punishments');
    }
};
