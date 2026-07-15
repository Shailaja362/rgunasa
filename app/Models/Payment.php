<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'event_id',
        'event_schedule_id',
        'cf_order_id',
        'cf_payment_id',
        'payment_method',
        'amount',
        'status',
    ];
}
