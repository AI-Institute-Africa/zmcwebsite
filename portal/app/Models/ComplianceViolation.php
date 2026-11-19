<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceViolation extends Model
{
    use HasFactory;

    protected $table = 'compliance_violations';

    protected $fillable = [
        'reference',
        'category',
        'status',
        'summary',
        'reported_by',
        'application_id',
    ];
}
