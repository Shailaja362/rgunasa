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

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->string('name')->unique();
            $table->string('code')->nullable();
            $table->enum('graduate_type',['ug','pg']);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('no action');
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., Professor, Assistant Professor
            $table->string('description')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs_department_programme');
    }
};
