<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{

    protected $fillable = [
        'event_id',
        'event_schedule_id',
        'student_id',
        'entry_time',
        'exit_time'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function get_grade()
    {
        return $this->belongsTo(StudentEventRegistration::class, 'student_id', 'student_id'); // filter by current registration's event_id
    }

    public function get_student_upload_proof()
    {
        return $this->hasMany(StudentUploadProof::class, 'student_id', 'student_id');
    }

    public function get_feedback()
    {
        return $this->hasMany(StudentFeedback::class, 'student_id', 'student_id');
    }

    public function grades()
    {
        return $this->hasMany(
            StudentEventRegistration::class,
            'student_id',
            'student_id'
        );
    }

    public function schedule()
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id');
    }
}
