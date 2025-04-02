<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('frequency')->default('daily');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_forever')->default(false);
            $table->time('time')->nullable();
            $table->boolean('completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('next_occurrence')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Create pivot table for reminder-user relationship
        Schema::create('reminder_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_user');
        Schema::dropIfExists('reminders');
    }
};
