<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnaccreditedReport extends Model
{
    use HasFactory;

    protected $table = 'unaccredited_reports';

    protected $fillable = ['reference','subject_name','entity_name','status','notes','reported_by'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
