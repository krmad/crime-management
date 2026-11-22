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
        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('crime_category_id')->nullable();
            $table->string('custom_crime_type', 255)->nullable();
            $table->index('crime_category_id', 'cases_crime_category_id_index');

            $table->foreign('crime_category_id')
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
        Schema::table('cases', function (Blueprint $table) {
            $table->dropForeign(['crime_category_id']);
            $table->dropIndex('cases_crime_category_id_index');
            $table->dropColumn(['crime_category_id', 'custom_crime_type']);
        });
    }
};
