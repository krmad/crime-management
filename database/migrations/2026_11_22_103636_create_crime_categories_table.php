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
        Schema::create('crime_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable();
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->string('code', 64)->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('source_reference', 255)->nullable();
            $table->timestamps();

            $table->index(['parent_id', 'sort_order']);
            $table->index(['level', 'is_active', 'parent_id']);

            $table->foreign('parent_id')
                ->references('id')
                ->on('crime_categories')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crime_categories');
    }
};
