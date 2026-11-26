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
        Schema::table('cases', function (Blueprint $table): void {
            $table->foreignId('judge_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('forwarded_for_review_at')->nullable();
            $table->timestamp('judicial_reopened_at')->nullable();
            $table->timestamp('judgment_finalized_at')->nullable();
            $table->index(['judge_id', 'created_at'], 'cases_judge_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->dropIndex('cases_judge_id_created_at_index');
            $table->dropForeign(['judge_id']);
            $table->dropColumn([
                'judge_id',
                'forwarded_for_review_at',
                'judicial_reopened_at',
                'judgment_finalized_at',
            ]);
        });
    }
};
