<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomDashboard extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'role_slug',
        'layout_config', 'is_active', 'created_by',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function widgets()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id')->orderBy('position');
    }

    public function activeWidgets()
    {
        return $this->hasMany(DashboardWidget::class, 'dashboard_id')
            ->where('is_active', true)
            ->orderBy('position');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRole($query, string $role)
    {
        return $query->where('role_slug', $role);
    }
}
