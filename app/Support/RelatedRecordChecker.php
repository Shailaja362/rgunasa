<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Counts rows in other tables that reference a record, and turns those
 * counts into the messages shown in the delete confirmation popup.
 */
class RelatedRecordChecker
{
    /**
     * @param  array<int, array{table: string, column: string, value: mixed, label: string}>  $checks
     * @return array<int, array{label: string, count: int}>
     */
    public static function counts(array $checks): array
    {
        $found = [];

        foreach ($checks as $check) {
            $count = DB::table($check['table'])->where($check['column'], $check['value'])->count();

            if ($count > 0) {
                $found[] = ['label' => $check['label'], 'count' => $count];
            }
        }

        return $found;
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $counts
     */
    public static function describe(array $counts): string
    {
        return collect($counts)
            ->map(fn($row) => $row['count'] . ' ' . $row['label'])
            ->join(', ', ' and ');
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $counts
     */
    public static function blockingMessage(string $noun, array $counts): string
    {
        return "Cannot delete. This {$noun} has " . self::describe($counts) . '.';
    }

    /**
     * @param  array<int, array{label: string, count: int}>  $counts
     */
    public static function confirmMessage(string $noun, array $counts): string
    {
        if (empty($counts)) {
            return "Are you sure you want to delete this {$noun}? This cannot be undone.";
        }

        return "Deleting this {$noun} will also permanently remove: " . self::describe($counts) . '. This cannot be undone.';
    }
}
