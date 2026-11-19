<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccreditationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'holder_user_id',
        'application_id',
        'certificate_no',
        'record_number',
        'accreditation_number',
        'status',
        'issued_at',
        'expires_at',
        'collected_at',
        'card_issued_at',
        'qr_token',
        'meta',
        'first_name',
        'surname',
        'other_names',
        'organisation',
        'category',
        'id_number',
        'passport_number',
        'photo_path',
        'marital_status',
        'sex',
        'date_of_birth',
        'birth_place',
        'nationality',
        'physical_address',
        'town',
        'province',
        'phone',
        'cell',
        'telephone',
        'email',
        'medium',
        'designation',
        'accreditation_year',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'collected_at' => 'datetime',
        'card_issued_at' => 'datetime',
        'date_of_birth' => 'date',
        'meta' => 'array',
        'accreditation_year' => 'integer',
    ];

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->other_names,
            $this->surname,
        ]);
        return implode(' ', $parts) ?: ($this->holder?->name ?? '—');
    }
}
