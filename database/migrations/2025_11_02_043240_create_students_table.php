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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('programme_id');
            $table->string('name');
            $table->string('batch')->nullable();
            $table->enum('semester', ['1','2', '3', '4', '5', '6', '7','8'])->nullable();
            $table->string('register_number')->nullable();
            $table->string('email')->unique();
            $table->string('mobile_number')->unique();
            $table->string('password');
            $table->string('profile_pic')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['m', 'f', 'o'])->comment('m -> Male, f -> Female , o -> others ');
            $table->enum('section', ['a', 'b', 'c', 'd', 'e', 'f', 'r'])->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('no action');
            $table->foreign('programme_id')->references('id')->on('programmes')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
