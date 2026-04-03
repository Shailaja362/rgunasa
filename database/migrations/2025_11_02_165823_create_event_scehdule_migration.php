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
        Schema::create('event_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->enum('section', ['a', 'b', 'c', 'd', 'e', 'f', 'r'])->nullable();
            $table->string('batch')->nullable();
            $table->enum('semester', ['1', '2', '3', '4', '5', '6', '7', '8'])->nullable();
            $table->enum('year', ['1', '2', '3', '4','5'])
                ->nullable()
                ->after('batch')
                ->comment('1 = First Year, 2 = Second Year, 3 = Third Year, 4 = Fourth Year,5 = Fifth Year');
            $table->date('event_date');
            $table->enum('is_reserve_date', ['y', 'n'])->default('n')->comment('Y - Yes , N - No');
            $table->unsignedInteger('seat_count');
            $table->decimal('credit_points', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('no action');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
