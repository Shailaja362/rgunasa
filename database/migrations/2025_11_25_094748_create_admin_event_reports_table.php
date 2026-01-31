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
        Schema::create('event_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('event_schedule_id');
            $table->unsignedBigInteger('department_id');
            $table->date('event_date')->nullable();
            $table->integer('male_count')->default(0);
            $table->integer('female_count')->default(0);
            $table->text('outcomes')->nullable();
            $table->text('feedback_summary')->nullable();
            $table->string('certificates')->nullable();
            $table->string('attendance_in')->nullable();
            $table->string('attendance_out')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('no action');
            $table->foreign('event_schedule_id')->references('id')->on('event_schedules')->onDelete('no action');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('no action');
        });

        Schema::create('event_report_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('report_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('id')->on('event_reports')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('admin_event_reports');
    }
};
