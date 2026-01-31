<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedule extends Model
{
    public function registrations()
    {
        return $this->hasMany(StudentEventRegistration::class, 'event_id','event_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function get_report()
    {
        return $this->hasOne(EventReport::class, 'event_schedule_id');
    }
}
