<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;
    protected $guard = 'admin';
    protected $hidden = ['password'];
    protected $fillable = [
        'role_id',
        'department_id',
        'designation_id',
        'name',
        'email',
        'mobile_number',
        'password',
        'emp_code',
        'security_code',
    ];

    public function get_role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function get_department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function get_designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
}
