<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageMetric extends Model
{
    protected $fillable = [
        'company_id',
        'month',
        'test_minutes',
        'parallel_runs',
        'storage_mb',
    ];
}

