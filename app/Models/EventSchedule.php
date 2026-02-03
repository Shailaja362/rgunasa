<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    protected $fillable = [
        'event_id',
        'department_id',
        'section',
        'event_date',
        'is_reserve_date',
        'seat_count',
    ];

    public function registrations()
    {
        return $this->hasMany(StudentEventRegistration::class, 'event_id', 'event_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function get_report()
    {
        return $this->hasOne(EventReport::class, 'event_schedule_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
