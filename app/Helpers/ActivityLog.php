<?php

namespace App\Helpers;

use App\Models\Activity;

class ActivityLog
{
    public static function add($title, $user)
    {
       if(isset($user->register_number)){
            $user_type = 'student';
       }

        if(!empty(session()->get('admin'))){
           $user_type = 'admin';
        }else if(!empty(session()->get('super_admin'))){
           $user_type = 'super_admin';
        }else if(!empty(session()->get('student')) || !empty(session()->get('register_student'))){
           $user_type = 'student';
        }

        Activity::create([
            'title' => $title,
            'user_name' => $user->name,
            'user_id' => $user->id,
            'user_type' => $user_type,
        ]);
    }
}
