<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentUploadProof extends Model
{
    protected $fillable = ['student_id', 'event_id', 'event_schedule_id', 'file_path','file_name','file_type'];

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
