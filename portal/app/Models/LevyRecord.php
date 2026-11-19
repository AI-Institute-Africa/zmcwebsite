<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevyRecord extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'gross_annual_turnover' => 'decimal:2',
        'levy_rate' => 'decimal:4',
        'levy_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'form_data' => 'array',
    ];

    public static function yearOptions(): array
    {
        $currentYear = now()->year;
        $firstPayment = static::query()->whereNotNull('payment_date')->min('payment_date');
        $firstYear = $firstPayment
            ? \Illuminate\Support\Carbon::parse($firstPayment)->year
            : $currentYear;

        return range($currentYear, min($currentYear, $firstYear));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(LevyDocument::class);
    }

    public static function generateReference(): string
    {
        $year = date('Y');
        $last = static::where('reference', 'like', "AP6-{$year}-%")
            ->orderByDesc('id')
            ->value('reference');

        $seq = 1;
        if ($last && preg_match('/AP6-\d{4}-(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('AP6-%s-%04d', $year, $seq);
    }
}
