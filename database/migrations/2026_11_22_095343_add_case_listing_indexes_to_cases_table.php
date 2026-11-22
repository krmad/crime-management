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
            $table->index(['created_by', 'created_at'], 'cases_created_by_created_at_index');

            $table->index(['investigator_id', 'created_at'], 'cases_investigator_id_created_at_index');

            $table->index(['created_at'], 'cases_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex('cases_created_by_created_at_index');

            $table->dropIndex('cases_investigator_id_created_at_index');

            $table->dropIndex('cases_created_at_index');
        });
    }
};
