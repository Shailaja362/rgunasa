<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * session/start_time/end_time/reserve_start_time/reserve_end_time used to
     * be one fixed value per event on the events table, so every section of
     * an event had to run at the same time. Moving them onto event_schedules
     * lets each department/section schedule carry its own timing, the same
     * way programme_id/section/event_date already work. The events table
     * columns are left in place (unused) rather than dropped, in case
     * something outside this pass still reads them.
     */
    public function up(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->enum('session', ['1', '2'])->nullable()->after('event_date')->comment('1 FN, 2 AN');
            $table->time('start_time')->nullable()->after('session');
            $table->time('end_time')->nullable()->after('start_time');
            $table->time('reserve_start_time')->nullable()->after('end_time');
            $table->time('reserve_end_time')->nullable()->after('reserve_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
