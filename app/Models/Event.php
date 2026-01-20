<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    public function registrations()
    {
        return $this->hasMany(StudentEventRegistration::class, 'event_id');
    }

    public function get_faculty()
    {
        return $this->belongsTo(Faculty::class, 'faculty_id');
    }

    public function get_task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }

    public function get_report()
    {
            return $this->hasMany(EventReport::class, 'event_id', 'id');
    }

    public function get_club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }

    public function studentUploads()
    {
        return $this->hasMany(StudentUploadProof::class, 'event_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(StudentFeedback::class, 'event_id');
    }


}
