<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'walk', 'meal', 'medication', 'blood_pressure', 'blood_sugar', etc.
            $table->string('description')->nullable(); // For medication name or other details
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->date('date')->useCurrent();
            $table->timestamps();

            // Add index for faster queries
            $table->index(['user_id', 'type', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_activities');
    }
}; 