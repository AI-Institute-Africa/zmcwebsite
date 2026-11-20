@extends('layouts.portal')
@section('title','Website Subscribers')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-checkbox-circle-fill fs-5"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 border-0 shadow-sm rounded-3" style="font-size:13px;">
        <i class="ri-error-warning-fill fs-5"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border border-slate-100">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="{{ route('staff.pr.dashboard') }}" class="btn btn-sm btn-light border rounded-pill">
                    <i class="ri-arrow-left-line"></i> Back to Dashboard
                </a>
            </div>
            <h1 class="fw-bold m-0" style="letter-spacing:-0.02em; color:#0f172a;">Website Subscribers</h1>
            <p class="text-muted m-0 small mt-1">Manage newsletter subscribers from the ZMC Website</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                <i class="ri-user-line me-1"></i>{{ Auth::user()->name }}
            </span>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#dbeafe;">
                        <i class="ri-user-add-line" style="font-size:22px;color:#2563eb;"></i>
                    </div>
                    <div>
                        <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Total Subscribers</div>
                        <div class="fw-bold" style="font-size:28px;color:#0f172a;">{{ number_format($totalCount) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#dcfce7;">
                        <i class="ri-calendar-check-line" style="font-size:22px;color:#16a34a;"></i>
                    </div>
                    <div>
                        <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">This Month</div>
                        <div class="fw-bold" style="font-size:28px;color:#0f172a;">{{ number_format(\App\Models\Subscriber::whereMonth('created_at', now()->month)->count()) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fef3c7;">
                        <i class="ri-calendar-line" style="font-size:22px;color:#d97706;"></i>
                    </div>
                    <div>
                        <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Today</div>
                        <div class="fw-bold" style="font-size:28px;color:#0f172a;">{{ number_format(\App\Models\Subscriber::whereDate('created_at', today())->count()) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3" style="width:48px;height:48px;background:#fce7f3;">
                        <i class="ri-line-chart-line" style="font-size:22px;color:#db2777;"></i>
                    </div>
                    <div>
                        <div class="text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">This Week</div>
                        <div class="fw-bold" style="font-size:28px;color:#0f172a;">{{ number_format(\App\Models\Subscriber::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count()) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="bg-white rounded-4 shadow-sm border p-3 mb-3">
        <div class="row align-items-center g-3">
            <div class="col-md-6">
                <form method="GET" action="{{ route('staff.pr.subscribers') }}" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="ri-search-line"></i>
                        </span>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control border-start-0" 
                            placeholder="Search by name or email..."
                            value="{{ request('search') }}"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line me-1"></i> Search
                    </button>
                    @if(request('search'))
                    <a href="{{ route('staff.pr.subscribers') }}" class="btn btn-light border">
                        <i class="ri-close-line"></i> Clear
                    </a>
                    @endif
                </form>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                        <i class="ri-download-line me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('staff.pr.subscribers.export.csv') }}">
                                <i class="ri-file-excel-line me-2"></i> Export as CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('staff.pr.subscribers.export.excel') }}">
                                <i class="ri-file-excel-2-line me-2"></i> Export as Excel (XLSX)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('staff.pr.subscribers.export.pdf') }}" target="_blank">
                                <i class="ri-file-pdf-line me-2"></i> Export as PDF
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Subscribers Table --}}
    <div class="bg-white rounded-4 shadow-sm border overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background:#f1f5f9; border-bottom:2px solid #e2e8f0;">
                    <tr>
                        <th class="px-4 py-3 text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">#</th>
                        <th class="px-4 py-3 text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Full Name</th>
                        <th class="px-4 py-3 text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Email Address</th>
                        <th class="px-4 py-3 text-muted text-uppercase" style="font-size:11px;letter-spacing:.06em;">Subscribed Date</th>
                        <th class="px-4 py-3 text-muted text-uppercase text-center" style="font-size:11px;letter-spacing:.06em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscribers as $index => $subscriber)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $subscribers->firstItem() + $index }}</td>
                        <td class="px-4 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width:32px;height:32px;background:#e0e7ff;color:#4f46e5;font-weight:600;font-size:13px;">
                                    {{ strtoupper(substr($subscriber->full_name, 0, 1)) }}
                                </div>
                                <span class="fw-semibold">{{ $subscriber->full_name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="mailto:{{ $subscriber->email }}" class="text-decoration-none">
                                <i class="ri-mail-line me-1"></i>{{ $subscriber->email }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            <div>{{ $subscriber->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $subscriber->created_at->format('h:i A') }}</small>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('staff.pr.subscribers.destroy', $subscriber) }}" 
                                  onsubmit="return confirm('Are you sure you want to remove this subscriber?')" 
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="ri-delete-bin-line"></i> Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-inbox-line" style="font-size:48px;"></i>
                                <p class="mt-2 mb-0">No subscribers found</p>
                                @if(request('search'))
                                <p class="small">Try adjusting your search criteria</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($subscribers->hasPages())
        <div class="p-3 border-top">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $subscribers->firstItem() }} to {{ $subscribers->lastItem() }} of {{ $subscribers->total() }} subscribers
                </div>
                <div>
                    {{ $subscribers->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
