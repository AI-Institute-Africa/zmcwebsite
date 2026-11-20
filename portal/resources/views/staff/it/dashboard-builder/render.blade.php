@extends('layouts.portal')

@section('title', $dashboard->name)

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
            <i class="ri-dashboard-3-line me-2"></i>{{ $dashboard->name }}
        </h1>
        @if($dashboard->description)
            <div class="text-muted mt-1" style="font-size:13px;">{{ $dashboard->description }}</div>
        @endif
    </div>

    @include('staff.it.dashboard-builder._widgets', ['widgets' => $dashboard->activeWidgets, 'widgetData' => $widgetData])
</div>
@endsection
