<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventReport extends Model
{
    public function get_event_image()
    {
        return $this->hasMany(EventReportImage::class, 'report_id');
    }

    public function get_event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function feedbacks()
    {
        return $this->hasMany(StudentFeedback::class, 'event_id', 'id');
    }

    public function student_uploads()
    {
        return $this->hasMany(StudentUploadProof::class, 'event_id', 'event_id');
    }

    public function get_department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
