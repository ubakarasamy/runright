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
        'duration_ms',
    ];
}

