<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reminder_user', function (Blueprint $table) {
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
            $table->boolean('skipped')->default(false)->after('completed_by');
            $table->string('skip_reason')->nullable()->after('skipped');
        });
    }

    public function down()
    {
        Schema::table('reminder_user', function (Blueprint $table) {
            $table->dropColumn(['completed_by', 'skipped', 'skip_reason']);
        });
    }
}; 