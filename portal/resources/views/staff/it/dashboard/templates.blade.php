@extends('layouts.portal')

@section('title', 'Card & Certificate Templates - IT Admin')

@section('content')
<div class="it-dashboard" style="font-family: 'Inter', sans-serif; color: #1e293b; background: #f8fafc; min-height: 100vh; padding: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border border-slate-100">
        <div>
            <h1 class="fw-bold m-0" style="letter-spacing: -0.02em; color: #0f172a;">Card & Certificate Templates</h1>
            <p class="text-slate-600 m-0 small mt-1">Manage accreditation card and certificate template designs</p>
        </div>
        <div>
            <a href="{{ route('staff.it.dashboard') }}" class="btn btn-slate-100 rounded-pill px-3 fw-bold small text-slate-600">
                <i class="ri-arrow-left-line"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <i class="ri-layout-masonry-line text-slate-400" style="font-size: 48px;"></i>
        <h2 class="fw-bold mt-3">Template Management</h2>
        <p class="text-slate-600">Card and certificate templates can be managed from the Production module.</p>
        @if(Route::has('staff.production.templates'))
        <a href="{{ route('staff.production.templates') }}" class="btn btn-dark rounded-pill px-4 mt-2">
            <i class="ri-external-link-line me-1"></i> Go to Production Templates
        </a>
        @endif
    </div>
</div>
@endsection
