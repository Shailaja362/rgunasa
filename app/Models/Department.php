<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public function get_programme()
    {
        return $this->belongsTo(Programme::class, 'department_id');
    }

    public function get_faculty()
    {
        return $this->belongsTo(Faculty::class, 'department_id');
    }

    public function get_student()
    {
        return $this->belongsTo(Student::class, 'department_id');
    }
}
