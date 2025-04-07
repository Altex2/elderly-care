<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('type')->nullable()->after('status');
            $table->string('medication_name')->nullable()->after('type');
            $table->string('medication_dosage')->nullable()->after('medication_name');
            $table->text('medication_instructions')->nullable()->after('medication_dosage');
            $table->integer('escalation_level')->default(1)->after('medication_instructions');
            $table->timestamp('last_escalated_at')->nullable()->after('escalation_level');
            $table->string('voice_command')->nullable()->after('last_escalated_at');
            $table->boolean('confirmation_required')->default(false)->after('voice_command');
            $table->timestamp('confirmation_deadline')->nullable()->after('confirmation_required');
            $table->boolean('repeat_until_confirmed')->default(false)->after('confirmation_deadline');
            $table->string('category')->nullable()->after('repeat_until_confirmed');
            $table->json('tags')->nullable()->after('category');
            $table->string('location')->nullable()->after('tags');
            $table->json('attachments')->nullable()->after('location');
            $table->json('notes')->nullable()->after('attachments');
        });

        // Create reminder confirmations table
        Schema::create('reminder_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('confirmation_type'); // voice, button, etc.
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create reminder escalations table
        Schema::create('reminder_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained()->onDelete('cascade');
            $table->foreignId('escalated_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('escalated_to')->constrained('users')->onDelete('cascade');
            $table->integer('level');
            $table->string('status'); // pending, acknowledged, resolved
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create voice logs table
        Schema::create('voice_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('command');
            $table->text('response')->nullable();
            $table->string('status'); // success, error, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_logs');
        Schema::dropIfExists('reminder_escalations');
        Schema::dropIfExists('reminder_confirmations');

        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'medication_name',
                'medication_dosage',
                'medication_instructions',
                'escalation_level',
                'last_escalated_at',
                'voice_command',
                'confirmation_required',
                'confirmation_deadline',
                'repeat_until_confirmed',
                'category',
                'tags',
                'location',
                'attachments',
                'notes'
            ]);
        });
    }
}; 