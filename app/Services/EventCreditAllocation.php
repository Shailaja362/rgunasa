<?php

namespace App\Services;

use App\Models\CreditPoint;
use App\Models\Programme;
use App\Services\Reports\StudentCreditReport;
use Illuminate\Support\Facades\DB;

/**
 * Stops a semester's credits being over-assigned when an event is saved.
 *
 * Credit Point Assign sets how many credits a semester requires (e.g. 1).
 * A student can only earn credits from event schedules open to their own
 * semester, programme, batch and section, so for every such group the credit
 * points of all active events' schedules together must not go above the
 * semester's required credits. Once they reach it, that semester's credits
 * are "closed" for the group and no more credit events can be created for it.
 *
 * Schedules without credit points (credit is optional) are never limited.
 * A semester with no required credits set in Credit Point Assign is not
 * limited either.
 */
class EventCreditAllocation
{
    /**
     * Returns an error message for the first over-assigned group, or null
     * if every schedule fits. `$departments` is the request's departments
     * array; `$eventId` is the event being edited (its own saved schedules
     * are replaced by the submitted ones, so they are not counted).
     */
    public static function firstError(array $departments, ?int $eventId = null): ?string
    {
        $submitted = collect($departments)
            ->map(fn($d) => self::schedule(
                $d['semester'] ?? [],
                $d['programme_id'] ?? [],
                $d['batch'] ?? [],
                $d['section'] ?? [],
                $d['credit_points'] ?? null,
            ) + ['schedule_id' => $d['schedule_id'] ?? null])
            ->filter(fn($s) => $s['credit'] > 0 && !empty($s['semester']))
            ->values();

        // When editing, the event's saved schedules (to spot unchanged ones).
        $saved = $eventId
            ? DB::table('event_schedules')->where('event_id', $eventId)->get()->keyBy('id')
                ->map(fn($r) => self::schedule(
                    explode(',', (string) $r->semester),
                    explode(',', (string) $r->programme_id),
                    explode(',', (string) $r->batch),
                    explode(',', (string) $r->section),
                    $r->credit_points,
                ))
            : collect();

        if ($submitted->isEmpty()) {
            return null;
        }

        $semesters = $submitted->pluck('semester')->flatten()->unique()->values();
        $required = CreditPoint::whereIn('semester', $semesters)
            ->pluck('credit_points', 'semester')
            ->map(fn($v) => (float) $v);

        // Credit schedules of other active events that share one of these semesters.
        $existing = DB::table('event_schedules as es')
            ->join('events as e', 'e.id', '=', 'es.event_id')
            ->where('e.is_active', 'y')
            ->where('es.credit_points', '>', 0)
            ->when($eventId, fn($q) => $q->where('e.id', '<>', $eventId))
            ->where(function ($q) use ($semesters) {
                foreach ($semesters as $sem) {
                    $q->orWhereRaw('FIND_IN_SET(?, es.semester)', [$sem]);
                }
            })
            ->get(['es.semester', 'es.programme_id', 'es.batch', 'es.section', 'es.credit_points'])
            ->map(fn($r) => self::schedule(
                explode(',', (string) $r->semester),
                explode(',', (string) $r->programme_id),
                explode(',', (string) $r->batch),
                explode(',', (string) $r->section),
                $r->credit_points,
            ))
            ->all();

        $counted = $existing;

        foreach ($submitted as $new) {
            // An edited schedule that gives no more credit, to no new group,
            // than it already did is allowed even if the semester is already
            // over-assigned, so older events can still be edited.
            $before = $new['schedule_id'] ? $saved->get($new['schedule_id']) : null;
            if ($before && self::addsNothing($new, $before)) {
                $counted[] = $new;
                continue;
            }

            foreach ($new['semester'] as $sem) {
                if (!isset($required[$sem])) {
                    continue; // no required credits set for this semester
                }
                $limit = $required[$sem];

                foreach ($new['programme_id'] as $prog) {
                    foreach ($new['batch'] as $batch) {
                        foreach ($new['section'] as $section) {
                            $assigned = 0.0;
                            foreach ($counted as $other) {
                                if (self::covers($other, $sem, $prog, $batch, $section)) {
                                    $assigned += $other['credit'];
                                }
                            }

                            if ($assigned + $new['credit'] > $limit + 0.0001) {
                                return self::message($sem, $prog, $batch, $section, $limit, $assigned, $new['credit']);
                            }
                        }
                    }
                }
            }

            // Later schedules in the same form count this one too.
            $counted[] = $new;
        }

        return null;
    }

    /** Normalised schedule: trimmed lists and a numeric credit. */
    private static function schedule($semester, $programme, $batch, $section, $credit): array
    {
        $clean = fn($list) => array_values(array_unique(array_filter(
            array_map(fn($v) => trim((string) $v), (array) $list),
            fn($v) => $v !== ''
        )));

        return [
            'semester'     => $clean($semester),
            'programme_id' => $clean($programme),
            'batch'        => $clean($batch),
            'section'      => array_map('strtolower', $clean($section)),
            'credit'       => is_numeric($credit) ? (float) $credit : 0.0,
        ];
    }

    /** Same or lower credit, and no semester/programme/batch/section added. */
    private static function addsNothing(array $new, array $before): bool
    {
        if ($new['credit'] > $before['credit'] + 0.0001) {
            return false;
        }
        foreach (['semester', 'programme_id', 'batch', 'section'] as $key) {
            // Empty means "everyone": only fine if it was "everyone" before too.
            if (empty($new[$key]) && !empty($before[$key])) {
                return false;
            }
            if (!empty($before[$key]) && array_diff($new[$key], $before[$key])) {
                return false;
            }
        }

        return true;
    }

    /** Whether a schedule is open to this group. An empty list means "everyone". */
    private static function covers(array $s, string $sem, string $prog, string $batch, string $section): bool
    {
        $open = fn(array $list, string $value) => empty($list) || in_array($value, $list, true);

        return in_array($sem, $s['semester'], true)
            && $open($s['programme_id'], $prog)
            && $open($s['batch'], $batch)
            && $open($s['section'], strtolower($section));
    }

    private static function message(string $sem, string $prog, string $batch, string $section, float $limit, float $assigned, float $credit): string
    {
        $n = fn(float $v) => StudentCreditReport::number($v);
        $programme = Programme::whereKey($prog)->value('name') ?? "Programme {$prog}";
        $group = "{$programme}, Batch {$batch}, Section " . strtoupper($section);

        if ($assigned >= $limit - 0.0001) {
            return "Semester {$sem} credits are already closed for {$group}: "
                . "{$n($assigned)} of {$n($limit)} credits are assigned to other events. "
                . 'Leave Credit Points empty to create this as a non-credit event.';
        }

        return "Semester {$sem} has only {$n($limit - $assigned)} credit(s) left for {$group} "
            . "({$n($assigned)} of {$n($limit)} assigned), but this event gives {$n($credit)}.";
    }
}
