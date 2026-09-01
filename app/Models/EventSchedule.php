<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    protected $fillable = [
        'event_id',
        'programme_id',
        'section',
        'event_date',
        'is_reserve_date',
        'seat_count',
        'batch',
        'semester',
        'credit_points'
    ];

    public function registrations()
    {
        return $this->hasMany(StudentEventRegistration::class, 'event_id', 'event_id');
    }
    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }
    /**
     * Cached per unique programme_id string for the life of the request, so
     * schedules sharing the same programme list (common within one event)
     * don't re-run the same lookup query for every row rendered.
     */
    private static array $programmeNamesCache = [];

    public function getProgrammeNamesAttribute()
    {
        if (empty($this->programme_id)) {
            return 'All Programmes';
        }
        if (array_key_exists($this->programme_id, self::$programmeNamesCache)) {
            return self::$programmeNamesCache[$this->programme_id];
        }
        $ids = array_map('trim', explode(',', $this->programme_id));
        $names = Programme::whereIn('id', $ids)->pluck('name')->implode(', ');
        self::$programmeNamesCache[$this->programme_id] = $names;
        return $names;
    }

    public function getSectionNamesAttribute()
    {
        if (empty($this->section)) {
            return 'All Sections';
        }
        return collect(explode(',', $this->section))
            ->map(fn($s) => strtoupper(trim($s)))
            ->implode(', ');
    }

    public function get_report()
    {
        return $this->hasOne(EventReport::class, 'event_schedule_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * batch/semester hold a comma-separated list of allowed values (or are
     * null/empty, meaning "open to all"). These helpers centralize that
     * matching so schedule eligibility checks don't rely on exact equality.
     */
    public function isOpenToBatch($batch): bool
    {
        if (empty($this->batch)) {
            return true;
        }
        if (empty($batch)) {
            return false;
        }
        return in_array((string) $batch, array_map('trim', explode(',', $this->batch)), true);
    }

    public function isOpenToSemester($semester): bool
    {
        if (empty($this->semester)) {
            return true;
        }
        if (empty($semester)) {
            return false;
        }
        return in_array((string) $semester, array_map('trim', explode(',', $this->semester)), true);
    }

    public function scopeOpenToBatch($query, $batch)
    {
        return $query->where(function ($q) use ($batch) {
            $q->whereNull('batch')->orWhere('batch', '');
            if (!empty($batch)) {
                $q->orWhereRaw('FIND_IN_SET(?, batch)', [$batch]);
            }
        });
    }

    public function scopeOpenToSemester($query, $semester)
    {
        return $query->where(function ($q) use ($semester) {
            $q->whereNull('semester')->orWhere('semester', '');
            if (!empty($semester)) {
                $q->orWhereRaw('FIND_IN_SET(?, semester)', [$semester]);
            }
        });
    }

    /**
     * Array variants: match a row that's open to at least one of the given
     * values (or open to all). Used where an admin filter allows selecting
     * several batches/semesters at once. An empty array skips the filter.
     */
    public function scopeOpenToAnyBatch($query, array $batches)
    {
        $batches = array_values(array_filter($batches));
        if (empty($batches)) {
            return $query;
        }

        return $query->where(function ($q) use ($batches) {
            $q->whereNull('batch')->orWhere('batch', '');
            foreach ($batches as $batch) {
                $q->orWhereRaw('FIND_IN_SET(?, batch)', [$batch]);
            }
        });
    }

    public function scopeOpenToAnySemester($query, array $semesters)
    {
        $semesters = array_values(array_filter($semesters));
        if (empty($semesters)) {
            return $query;
        }

        return $query->where(function ($q) use ($semesters) {
            $q->whereNull('semester')->orWhere('semester', '');
            foreach ($semesters as $semester) {
                $q->orWhereRaw('FIND_IN_SET(?, semester)', [$semester]);
            }
        });
    }

    /**
     * programme_id/section hold a comma-separated list of allowed values (or
     * are null/empty, meaning "open to all"), same convention as batch/semester.
     */
    public function isOpenToProgramme($programmeId): bool
    {
        if (empty($this->programme_id)) {
            return true;
        }
        if (empty($programmeId)) {
            return false;
        }
        return in_array((string) $programmeId, array_map('trim', explode(',', $this->programme_id)), true);
    }

    public function isOpenToSection($section): bool
    {
        if (empty($this->section)) {
            return true;
        }
        if (empty($section)) {
            return false;
        }
        return in_array((string) $section, array_map('trim', explode(',', $this->section)), true);
    }

    public function scopeOpenToProgramme($query, $programmeId)
    {
        return $query->where(function ($q) use ($programmeId) {
            $q->whereNull('programme_id')->orWhere('programme_id', '');
            if (!empty($programmeId)) {
                $q->orWhereRaw('FIND_IN_SET(?, programme_id)', [$programmeId]);
            }
        });
    }

    public function scopeOpenToSection($query, $section)
    {
        return $query->where(function ($q) use ($section) {
            $q->whereNull('section')->orWhere('section', '');
            if (!empty($section)) {
                $q->orWhereRaw('FIND_IN_SET(?, section)', [$section]);
            }
        });
    }
}
