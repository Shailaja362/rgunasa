<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'student_id',
        'event_schedule_id',
        'status',
        'grade',
        'registered_at',
        'created_at',
        'updated_at'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function get_student_proof()
    {
        return $this->hasMany(StudentUploadProof::class, 'student_id','student_id');
    }

    public function get_attendance()
    {
        return $this->hasMany(StudentAttendance::class, 'student_id', 'student_id');
    }

    public function get_event_attendance()
    {
        return $this->hasMany(StudentAttendance::class, 'event_id', 'event_id');
    }

    public function schedule()
    {
        return $this->belongsTo(EventSchedule::class, 'event_id', 'event_id');
    }

    public function get_event_schedule()
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id',);
    }
}
