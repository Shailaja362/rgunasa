<?php

namespace App\Traits;

use App\Models\EventSchedule;

trait ResolvesEventSchedule
{
    protected function resolveSchedule($eventId, $programmeId, $date,$section)
    {
        return EventSchedule::where('event_id', $eventId)
            ->where('programme_id', $programmeId)
            ->where('section', $section)
            ->where(function ($q) use ($date) {
                $q->whereDate('event_date', $date);
            })
            ->first();
    }
}
