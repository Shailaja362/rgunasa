<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'admin_id',
        'report',
        'type',
        'filters',
        'status',
        'total_rows',
        'file_path',
        'file_name',
        'error',
        'completed_at',
    ];

    protected $casts = [
        'filters'      => 'array',
        'completed_at' => 'datetime',
    ];

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }
}
