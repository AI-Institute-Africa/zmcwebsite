<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormBuilderRevision extends Model
{
    protected $fillable = [
        'form_type',
        'version',
        'schema',
        'change_summary',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'schema' => 'array',
        'version' => 'integer',
        'published_at' => 'datetime',
    ];

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
