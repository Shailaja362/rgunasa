<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing month-based values to their day equivalent (1 month = 30 days)
        // before renaming, so existing events' cooldown periods stay the same length.
        DB::table('events')->whereNotNull('duration_months')->update([
            'duration_months' => DB::raw('duration_months * 30'),
        ]);

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('duration_months', 'duration_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('duration_days', 'duration_months');
        });

        DB::table('events')->whereNotNull('duration_months')->update([
            'duration_months' => DB::raw('duration_months / 30'),
        ]);
    }
};
