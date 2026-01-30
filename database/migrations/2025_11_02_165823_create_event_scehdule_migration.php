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
            $table->unsignedBigInteger('department_id');
            $table->enum('section', ['a','b','c']);
            $table->date('event_date');
            $table->date('reserve_date')->nullable();
            $table->unsignedInteger('seat_count');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('no action');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
   
    }
};
