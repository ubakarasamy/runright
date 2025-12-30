<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'token',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

