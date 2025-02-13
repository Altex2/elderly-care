<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('status');
            $table->timestamp('completed_at')->nullable()->after('completed');
        });
    }

    public function down()
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['completed', 'completed_at']);
        });
    }
};
