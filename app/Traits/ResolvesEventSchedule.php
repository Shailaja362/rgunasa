<?php

namespace App\Traits;

use App\Models\EventSchedule;

trait ResolvesEventSchedule
{
    protected function resolveSchedule($eventId, $programmeId, $date, $section, $batch, $semester)
    {
        return EventSchedule::where('event_id', $eventId)
            ->openToProgramme($programmeId)
            ->openToSection($section)
            ->openToBatch($batch)
            ->openToSemester($semester)
//            ->where(function ($q) use ($date) {
//                $q->whereDate('event_date', $date);
//            })
            ->first();
    }
}
