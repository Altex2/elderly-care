<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('caregiver_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caregiver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['caregiver_id', 'patient_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('caregiver_patient');
    }
};
