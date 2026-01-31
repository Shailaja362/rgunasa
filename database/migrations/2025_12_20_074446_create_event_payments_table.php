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
        Schema::create('event_payments', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('event_schedule_id');
            $table->string('order_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('signature')->nullable();
            $table->enum('status', ['created', 'paid', 'failed'])->default('created');
            $table->text('failure_reason')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->foreign('event_schedule_id')->references('id')->on('event_schedules')->onDelete('no action');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('no action');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_payments');
    }
};
