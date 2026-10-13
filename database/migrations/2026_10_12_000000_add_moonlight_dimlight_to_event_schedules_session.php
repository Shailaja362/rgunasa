<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->enum('session', ['1', '2', '3', '4'])->nullable()->comment('1 FN, 2 AN, 3 Moonlight, 4 Dimlight')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->enum('session', ['1', '2'])->nullable()->comment('1 FN, 2 AN')->change();
        });
    }
};
