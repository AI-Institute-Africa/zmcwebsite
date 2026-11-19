<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_user_id', 'application_id', 'entity_name', 'organisation_name',
        'registration_no', 'record_number', 'status',
        'directors', 'directors_gender', 'shareholding',
        'physical_address', 'town', 'province', 'phone', 'cell', 'email', 'website',
        'category', 'registration_status', 'registration_date', 'registration_year',
        'contact_person_name', 'contact_person_email', 'contact_person_phone',
        'issued_at', 'expires_at', 'collected_at', 'qr_token', 'meta',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'expires_at' => 'date',
        'collected_at' => 'datetime',
        'registration_date' => 'date',
        'meta' => 'array',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->organisation_name ?: $this->entity_name ?: '—';
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(MediaHousePublication::class);
    }
}
