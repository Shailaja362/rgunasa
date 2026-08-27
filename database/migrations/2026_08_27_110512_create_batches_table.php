<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Backfill from batch values already in use so existing records
        // stay selectable once free-text batch inputs become dropdowns.
        $existingBatches = DB::table('students')
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->pluck('batch')
            ->merge(
                DB::table('event_schedules')
                    ->whereNotNull('batch')
                    ->where('batch', '!=', '')
                    ->pluck('batch')
            )
            ->unique()
            ->values();

        $now = now();
        $rows = $existingBatches->map(fn($name) => [
            'name'       => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (!empty($rows)) {
            DB::table('batches')->insertOrIgnore($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
