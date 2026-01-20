<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentUploadProof extends Model
{
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Upload belongs to event (optional but recommended)
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
