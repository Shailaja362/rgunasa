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

        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('mobile_number')->nullable();
            $table->string('faculty_code');
            $table->string('profile_pic')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('no action');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('no action');
        });

        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faculty_id');
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('no action');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('faculty_id');
            $table->unsignedBigInteger('created_by');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('title');
            $table->text('description');
            $table->time('start_time');
            $table->time('end_time');
            $table->time('reserve_start_time');
            $table->time('reserve_end_time');
            $table->enum('event_type', ['paid', 'free']);
            $table->integer('duration_months')->nullable()->comment('Event validity duration in months');
            $table->string('location');
            $table->enum('session', ['1', '2'])
                ->comment('1 FN, 2 AN');
            $table->text('eligibility_criteria');
            $table->date('end_registration');
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('banner_image')->nullable(); // store image path
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->enum('is_technical_event', ['y', 'n'])->default('n')->comment('Y - Yes , N - No');
            $table->timestamps();

            $table->foreign('faculty_id')->references('id')->on('faculties')->onDelete('no action');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('no action');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('no action');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
