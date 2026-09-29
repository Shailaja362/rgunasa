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

        DB::statement('
            UPDATE event_schedules
            INNER JOIN events ON events.id = event_schedules.event_id
            SET
                event_schedules.session = events.session,
                event_schedules.start_time = events.start_time,
                event_schedules.end_time = events.end_time,
                event_schedules.reserve_start_time = events.reserve_start_time,
                event_schedules.reserve_end_time = events.reserve_end_time
        ');

        // These are kept (not dropped) as a safety net, but new event saves no
        // longer write to them, so they must accept NULL going forward.
        DB::statement("ALTER TABLE events MODIFY session ENUM('1', '2') NULL COMMENT '1 FN, 2 AN'");
        DB::statement('ALTER TABLE events MODIFY start_time TIME NULL');
        DB::statement('ALTER TABLE events MODIFY end_time TIME NULL');
        DB::statement('ALTER TABLE events MODIFY reserve_start_time TIME NULL');
        DB::statement('ALTER TABLE events MODIFY reserve_end_time TIME NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE events MODIFY session ENUM('1', '2') NOT NULL COMMENT '1 FN, 2 AN'");
        DB::statement('ALTER TABLE events MODIFY start_time TIME NOT NULL');
        DB::statement('ALTER TABLE events MODIFY end_time TIME NOT NULL');
        DB::statement('ALTER TABLE events MODIFY reserve_start_time TIME NOT NULL');
        DB::statement('ALTER TABLE events MODIFY reserve_end_time TIME NOT NULL');

        Schema::table('event_schedules', function (Blueprint $table) {
            $table->dropColumn(['session', 'start_time', 'end_time', 'reserve_start_time', 'reserve_end_time']);
        });
    }
};
