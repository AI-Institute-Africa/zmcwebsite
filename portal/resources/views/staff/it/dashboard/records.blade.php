@extends('layouts.portal')
@section('title', 'Records Management - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h2 class="fw-bold m-0" style="color:#0f172a;">Records Management</h2>
            <p class="text-muted m-0 small mt-1">CRUD tools for technical and administrative records</p>
        </div>
        <a href="{{ route('staff.it.dashboard') }}" class="btn btn-light rounded-pill px-3 fw-bold small border"><i class="ri-arrow-left-line"></i> Dashboard</a>
    </div>

    <div class="row g-4">
        @php
        $cards = [
            ['label' => 'Users', 'count' => $users, 'icon' => 'ri-user-line', 'color' => 'primary', 'route' => 'staff.it.users-mgmt'],
            ['label' => 'Regions', 'count' => $regions, 'icon' => 'ri-map-pin-line', 'color' => 'success', 'route' => 'staff.it.regions-mgmt'],
            ['label' => 'Locations', 'count' => $locations, 'icon' => 'ri-building-line', 'color' => 'info', 'route' => 'staff.it.regions-mgmt'],
            ['label' => 'Devices', 'count' => $devices, 'icon' => 'ri-printer-line', 'color' => 'warning', 'route' => 'staff.it.devices'],
            ['label' => 'Roles', 'count' => $roles, 'icon' => 'ri-shield-user-line', 'color' => 'danger', 'route' => 'staff.it.roles-mgmt'],
        ];
        @endphp

        @foreach($cards as $card)
        <div class="col-md-4 col-lg-3">
            <a href="{{ route($card['route']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-2 rounded-3 bg-{{ $card['color'] }}-subtle">
                            <i class="{{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                        </div>
                        <h3 class="fw-bold m-0" style="color:#0f172a;">{{ number_format($card['count']) }}</h3>
                    </div>
                    <p class="text-muted small fw-bold m-0">{{ $card['label'] }}</p>
                </div>
            </a>
        </div>
        @endforeach
    </div>
</div>
@endsection
