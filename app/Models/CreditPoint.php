<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPoint extends Model
{
    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id', 'id');
    }
}
