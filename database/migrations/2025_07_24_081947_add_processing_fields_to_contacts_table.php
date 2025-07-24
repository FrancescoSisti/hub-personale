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
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('read');
            $table->boolean('processed_by_job')->default(false)->after('processed_at');
            
            // Add index for processing queries
            $table->index(['processed_by_job', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['processed_by_job', 'processed_at']);
            $table->dropColumn(['processed_at', 'processed_by_job']);
        });
    }
};
