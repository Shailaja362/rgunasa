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
        Schema::create('student_event_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('event_schedule_id');
            $table->timestamp('registered_at')->nullable();
            $table->enum('status', ['1', '2', '3', '4'])->comment('1 - Registered, 2 - Approved, 3 - Completed, 4 - Cancelled');
            $table->enum('grade', ['a', 'b', 'c', 'd'])->comment('A - Winner, B - Runner Up, C - Completed, D - Disqualified')->nullable();
            $table->timestamps();

            $table->foreign('event_schedule_id')->references('id')->on('event_schedules')->onDelete('no action');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('no action');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
