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
        Schema::table('notifications', function (Blueprint $table) {
            // First check if the column exists
            if (Schema::hasColumn('notifications', 'data')) {
                // Change the data column to json type
                $table->json('data')->change();
            } else {
                // Add the column if it doesn't exist
                $table->json('data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Change back to text if needed
            if (Schema::hasColumn('notifications', 'data')) {
                $table->text('data')->change();
            }
        });
    }
};
