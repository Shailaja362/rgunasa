<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFeedback extends Model
{
   protected $table = 'student_feedbacks';
   protected $fillable = [
        'student_id',
        'event_id',
        'ratings',
        'comments',
        'event_schedule_id'
    ];
   protected $casts = [
        'ratings' => 'array',
    ];
    // Feedback belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Feedback belongs to an event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // Get uploads via student_id (hasManyThrough alternative)
    public function uploads()
    {
        return $this->hasMany(
            StudentUploadProof::class,
            'student_id',
            'student_id',
            'student_id',
            'student_id'
        );
    }


}
