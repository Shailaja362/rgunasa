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
        // semester now stores a comma-separated list (e.g. "1,2,3") since a
        // schedule row can be open to multiple semesters at once, which an
        // enum column can't hold.
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_schedules', function (Blueprint $table) {
            $table->enum('semester', ['1', '2', '3', '4', '5', '6', '7', '8'])->nullable()->change();
        });
    }
};
