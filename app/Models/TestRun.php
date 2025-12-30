<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestRun extends Model
{
	protected $fillable = [
        'company_id',
        'project_id',
        'source',
        'status',
        'result',
        'started_at',
        'finished_at',
        'duration_ms',
        'error_message',
        'logs',
    ];

    protected $casts = [
        'logs' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}

