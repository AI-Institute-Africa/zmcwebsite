@extends('layouts.portal')

@section('title', 'Edit Dashboard: ' . $dashboard->name)

@section('content')
<div class="container-fluid px-4">

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 mb-3">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                <i class="ri-pencil-ruler-2-line me-2"></i>Edit: {{ $dashboard->name }}
            </h1>
            <div class="text-muted mt-1" style="font-size:13px;">Configure dashboard settings and widgets</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.dashboard-builder.preview', $dashboard) }}" class="btn btn-sm btn-outline-primary">
                <i class="ri-eye-line me-1"></i> Preview
            </a>
            <a href="{{ route('staff.it.dashboard-builder') }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h2 class="fw-bold m-0">Dashboard Settings</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.it.dashboard-builder.update', $dashboard) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $dashboard->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Assign to Role</label>
                            <select name="role_slug" class="form-select">
                                <option value="">-- All Roles --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role }}" {{ $dashboard->role_slug === $role ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ $dashboard->description }}</textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="isActive" {{ $dashboard->is_active ? 'checked' : '' }}>
                            <label for="isActive" class="form-check-label">Active</label>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">
                            <i class="ri-save-line me-1"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="fw-bold m-0">Add Widget</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.it.dashboard-builder.add-widget', $dashboard) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Widget Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-sm" required placeholder="e.g. Total Applications">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Widget Type <span class="text-danger">*</span></label>
                                <select name="widget_type" class="form-select form-select-sm" required>
                                    @foreach($widgetTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Data Source <span class="text-danger">*</span></label>
                                <select name="data_source" class="form-select form-select-sm" required>
                                    @foreach($dataSources as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Size</label>
                                <select name="size" class="form-select form-select-sm">
                                    @foreach($sizeOptions as $key => $label)
                                        <option value="{{ $key }}" {{ $key === 'col-md-6' ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Color</label>
                                <select name="color" class="form-select form-select-sm">
                                    <option value="primary">Blue</option>
                                    <option value="success">Green</option>
                                    <option value="warning">Yellow</option>
                                    <option value="danger">Red</option>
                                    <option value="dark">Dark</option>
                                    <option value="info">Cyan</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Icon</label>
                                <input type="text" name="icon" class="form-control form-control-sm" value="ri-bar-chart-box-line" placeholder="ri-icon-name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Limit</label>
                                <input type="number" name="limit" class="form-control form-control-sm" value="10" min="1" max="100">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm">
                            <i class="ri-add-line me-1"></i> Add Widget
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="fw-bold m-0">Widgets ({{ $dashboard->widgets->count() }})</h4>
                </div>
                <div class="card-body p-0">
                    @if($dashboard->widgets->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="ri-layout-grid-line" style="font-size:48px; opacity:0.3;"></i>
                            <p class="mt-2">No widgets added yet. Use the form on the left to add widgets.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size:13px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Data Source</th>
                                        <th>Size</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dashboard->widgets as $widget)
                                    <tr>
                                        <td>{{ $widget->position }}</td>
                                        <td class="fw-semibold">{{ $widget->title }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $widgetTypes[$widget->widget_type] ?? $widget->widget_type }}</span></td>
                                        <td><code style="font-size:11px;">{{ $widget->config['data_source'] ?? '-' }}</code></td>
                                        <td>{{ $sizeOptions[$widget->size] ?? $widget->size }}</td>
                                        <td>
                                            <form action="{{ route('staff.it.dashboard-builder.remove-widget', [$dashboard, $widget]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this widget?')">
                                                @csrf
                                                <button class="btn btn-sm zmc-icon-btn btn-outline-danger">
                                                    <i class="ri-delete-bin-line"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
