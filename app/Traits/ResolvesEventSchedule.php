<?php

namespace App\Traits;

use App\Models\EventSchedule;

trait ResolvesEventSchedule
{
    protected function resolveSchedule($eventId, $departmentId, $date,$section)
    {

        return EventSchedule::where('event_id', $eventId)
            ->where('department_id', $departmentId)
            ->where('section', $section)
            ->where(function ($q) use ($date) {
                $q->whereDate('event_date', $date);
            })
            ->first();
    }
}
