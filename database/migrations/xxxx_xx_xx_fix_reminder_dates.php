<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Fix any string dates to proper datetime format
        DB::table('reminders')->whereRaw('next_occurrence REGEXP ?', ['^[0-9]{4}-[0-9]{2}-[0-9]{2}.*$'])->update([
            'next_occurrence' => DB::raw('STR_TO_DATE(next_occurrence, "%Y-%m-%d %H:%i:%s")')
        ]);

        DB::table('reminders')->whereRaw('completed_at REGEXP ?', ['^[0-9]{4}-[0-9]{2}-[0-9]{2}.*$'])->update([
            'completed_at' => DB::raw('STR_TO_DATE(completed_at, "%Y-%m-%d %H:%i:%s")')
        ]);

        // Ensure columns are proper datetime type
        Schema::table('reminders', function (Blueprint $table) {
            $table->datetime('next_occurrence')->change();
            $table->datetime('completed_at')->nullable()->change();
        });
    }

    public function down()
    {
        // No need for down method as we're just fixing data format
    }
};
