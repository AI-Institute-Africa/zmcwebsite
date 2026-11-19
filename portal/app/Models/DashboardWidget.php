<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $fillable = [
        'dashboard_id', 'widget_type', 'title',
        'config', 'position', 'size', 'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function dashboard()
    {
        return $this->belongsTo(CustomDashboard::class, 'dashboard_id');
    }

    public static function widgetTypes(): array
    {
        return [
            'stats_card' => 'Statistics Card',
            'bar_chart' => 'Bar Chart',
            'line_chart' => 'Line Chart',
            'pie_chart' => 'Pie / Doughnut Chart',
            'table' => 'Data Table',
            'recent_list' => 'Recent Items List',
            'counter' => 'Counter',
        ];
    }

    public static function dataSources(): array
    {
        return [
            'total_applications' => 'Total Applications',
            'applications_by_status' => 'Applications by Status',
            'applications_by_type' => 'Applications by Type',
            'total_users' => 'Total Users',
            'users_by_role' => 'Users by Role',
            'recent_applications' => 'Recent Applications',
            'recent_users' => 'Recent Users',
            'monthly_applications' => 'Monthly Applications Trend',
            'payment_summary' => 'Payment Summary',
            'pending_reviews' => 'Pending Reviews Count',
            'approved_today' => 'Approved Today',
            'rejected_count' => 'Returned for Correction',
        ];
    }

    public static function sizeOptions(): array
    {
        return [
            'col-md-3' => '25% Width',
            'col-md-4' => '33% Width',
            'col-md-6' => '50% Width',
            'col-md-8' => '66% Width',
            'col-md-12' => 'Full Width',
        ];
    }
}
