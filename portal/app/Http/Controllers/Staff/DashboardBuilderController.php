<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CustomDashboard;
use App\Models\DashboardWidget;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DashboardBuilderController extends Controller
{
    public function index()
    {
        $dashboards = CustomDashboard::withCount('widgets')->latest()->get();
        $roles = Role::orderBy('name')->pluck('name');

        return view('staff.it.dashboard-builder.index', compact('dashboards', 'roles'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');
        $widgetTypes = DashboardWidget::widgetTypes();
        $dataSources = DashboardWidget::dataSources();
        $sizeOptions = DashboardWidget::sizeOptions();

        return view('staff.it.dashboard-builder.create', compact('roles', 'widgetTypes', 'dataSources', 'sizeOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'role_slug' => 'nullable|string|max:80',
            'is_active' => 'boolean',
        ]);

        $dashboard = CustomDashboard::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(4),
            'description' => $request->description,
            'role_slug' => $request->role_slug,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => session('user_id'),
        ]);

        return redirect()->route('staff.it.dashboard-builder.edit', $dashboard)
            ->with('success', 'Dashboard created. Now add widgets.');
    }

    public function edit(CustomDashboard $dashboard)
    {
        $dashboard->load('widgets');
        $roles = Role::orderBy('name')->pluck('name');
        $widgetTypes = DashboardWidget::widgetTypes();
        $dataSources = DashboardWidget::dataSources();
        $sizeOptions = DashboardWidget::sizeOptions();

        return view('staff.it.dashboard-builder.edit', compact('dashboard', 'roles', 'widgetTypes', 'dataSources', 'sizeOptions'));
    }

    public function update(Request $request, CustomDashboard $dashboard)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'role_slug' => 'nullable|string|max:80',
            'is_active' => 'boolean',
        ]);

        $dashboard->update([
            'name' => $request->name,
            'description' => $request->description,
            'role_slug' => $request->role_slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('staff.it.dashboard-builder')
            ->with('success', 'Dashboard updated.');
    }

    public function destroy(CustomDashboard $dashboard)
    {
        $dashboard->delete();
        return redirect()->route('staff.it.dashboard-builder')
            ->with('success', 'Dashboard deleted.');
    }

    public function addWidget(Request $request, CustomDashboard $dashboard)
    {
        $request->validate([
            'widget_type' => 'required|string|in:' . implode(',', array_keys(DashboardWidget::widgetTypes())),
            'title' => 'required|string|max:200',
            'data_source' => 'required|string|in:' . implode(',', array_keys(DashboardWidget::dataSources())),
            'size' => 'required|string|in:' . implode(',', array_keys(DashboardWidget::sizeOptions())),
            'color' => 'nullable|string|max:30',
            'icon' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $maxPos = $dashboard->widgets()->max('position') ?? 0;

        $dashboard->widgets()->create([
            'widget_type' => $request->widget_type,
            'title' => $request->title,
            'config' => [
                'data_source' => $request->data_source,
                'color' => $request->color ?? 'primary',
                'icon' => $request->icon ?? 'ri-bar-chart-box-line',
                'limit' => $request->limit ?? 10,
            ],
            'position' => $maxPos + 1,
            'size' => $request->size,
            'is_active' => true,
        ]);

        return redirect()->route('staff.it.dashboard-builder.edit', $dashboard)
            ->with('success', 'Widget added.');
    }

    public function removeWidget(CustomDashboard $dashboard, DashboardWidget $widget)
    {
        if ($widget->dashboard_id !== $dashboard->id) abort(403);
        $widget->delete();

        return redirect()->route('staff.it.dashboard-builder.edit', $dashboard)
            ->with('success', 'Widget removed.');
    }

    public function preview(CustomDashboard $dashboard)
    {
        $dashboard->load('activeWidgets');
        $widgetData = $this->resolveWidgetData($dashboard->activeWidgets);

        return view('staff.it.dashboard-builder.preview', compact('dashboard', 'widgetData'));
    }

    public function renderForRole(string $slug)
    {
        $dashboard = CustomDashboard::where('slug', $slug)->active()->firstOrFail();
        $dashboard->load('activeWidgets');
        $widgetData = $this->resolveWidgetData($dashboard->activeWidgets);

        return view('staff.it.dashboard-builder.render', compact('dashboard', 'widgetData'));
    }

    private function resolveWidgetData($widgets)
    {
        $data = [];
        foreach ($widgets as $widget) {
            $source = $widget->config['data_source'] ?? null;
            $limit = $widget->config['limit'] ?? 10;
            $data[$widget->id] = $this->fetchDataForSource($source, $limit);
        }
        return $data;
    }

    private function fetchDataForSource(?string $source, int $limit = 10)
    {
        return match ($source) {
            'total_applications' => Application::count(),
            'applications_by_status' => Application::selectRaw('status, count(*) as cnt')
                ->groupBy('status')->orderByDesc('cnt')->limit($limit)->pluck('cnt', 'status')->toArray(),
            'applications_by_type' => Application::selectRaw('application_type, count(*) as cnt')
                ->groupBy('application_type')->pluck('cnt', 'application_type')->toArray(),
            'total_users' => User::count(),
            'users_by_role' => User::selectRaw('COALESCE(role, account_type) as r, count(*) as cnt')
                ->groupBy('r')->pluck('cnt', 'r')->toArray(),
            'recent_applications' => Application::with('applicant')
                ->latest()->limit($limit)
                ->get(['id', 'reference', 'application_type', 'status', 'applicant_user_id', 'created_at'])
                ->toArray(),
            'recent_users' => User::latest()->limit($limit)
                ->get(['id', 'first_name', 'surname', 'email', 'account_type', 'created_at'])
                ->toArray(),
            'monthly_applications' => (function() use ($limit) {
                $isSqlite = \DB::getDriverName() === 'sqlite';
                $monthExpr = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
                return Application::selectRaw("{$monthExpr} as month, count(*) as cnt")
                    ->where('created_at', '>=', now()->subMonths(12))
                    ->groupByRaw("{$monthExpr}")
                    ->orderBy('month')
                    ->pluck('cnt', 'month')->toArray();
            })(),
            'payment_summary' => [
                'total' => \DB::table('payments')->count(),
                'completed' => \DB::table('payments')->where('status', 'completed')->count(),
                'pending' => \DB::table('payments')->where('status', 'pending')->count(),
            ],
            'pending_reviews' => Application::whereIn('status', ['submitted', 'under_review'])->count(),
            'approved_today' => Application::where('status', 'approved')
                ->whereDate('updated_at', today())->count(),
            'rejected_count' => Application::whereIn('status', ['officer_rejected', 'registrar_rejected'])->count(),
            default => 0,
        };
    }
}
