@extends('layouts.portal')

@section('title', 'Preview: ' . $dashboard->name)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
                <i class="ri-eye-line me-2"></i>Preview: {{ $dashboard->name }}
            </h1>
            @if($dashboard->description)
                <div class="text-muted mt-1" style="font-size:13px;">{{ $dashboard->description }}</div>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.it.dashboard-builder.edit', $dashboard) }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-pencil-line me-1"></i> Edit
            </a>
            <a href="{{ route('staff.it.dashboard-builder') }}" class="btn btn-sm btn-outline-dark">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>
    </div>

    @include('staff.it.dashboard-builder._widgets', ['widgets' => $dashboard->activeWidgets, 'widgetData' => $widgetData])
</div>
@endsection
