<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Student extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $guard = 'student';
    protected $fillable = ['department_id', 'programme_id', 'mobile_number', 'name', 'email', 'password', 'date_of_birth', 'gender', 'register_number', 'section', 'semester', 'batch'];
    protected $hidden = ['password'];

    public function get_department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function get_programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function uploads()
    {
        return $this->hasMany(StudentUploadProof::class, 'student_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(StudentFeedback::class, 'student_id');
    }

    public function registrations()
    {
        return $this->hasMany(StudentEventRegistration::class, 'student_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
