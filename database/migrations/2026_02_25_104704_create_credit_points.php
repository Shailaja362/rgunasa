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
        Schema::create('credit_points', function (Blueprint $table) {
            $table->id();
            $table->enum('semester', ['1', '2', '3', '4', '5', '6', '7', '8'])->nullable();
            $table->decimal('credit_points', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programme_wise_credit_points');
    }
};
