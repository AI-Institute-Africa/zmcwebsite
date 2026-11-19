@extends('layouts.staff')

@section('title', 'Application Detail')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="fw-bold mb-0">Application {{ $application->reference }}</h4>
      <div class="text-muted small">Read-only view • Applicant: {{ $application->applicant?->name ?? 'N/A' }}</div>
    </div>
    <a href="{{ route('staff.it.dashboard', ['tab' => 'monitoring']) }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri-arrow-left-line me-1"></i>Back
    </a>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h6 class="fw-bold">Summary</h6>
          <div class="row small">
            <div class="col-6 text-muted">Type</div>
            <div class="col-6 fw-semibold">{{ ucfirst($application->application_type) }} / {{ ucfirst($application->request_type) }}</div>

            <div class="col-6 text-muted mt-2">Status</div>
            <div class="col-6 mt-2 fw-semibold">{{ ucwords(str_replace('_',' ', $application->status)) }}</div>

            <div class="col-6 text-muted mt-2">Payment</div>
            <div class="col-6 mt-2 fw-semibold">{{ ucwords(str_replace('_',' ', $application->payment_status ?? 'n/a')) }}</div>

            <div class="col-6 text-muted mt-2">Submitted</div>
            <div class="col-6 mt-2">{{ optional($application->submitted_at)->format('Y-m-d H:i') ?? '—' }}</div>

            <div class="col-6 text-muted mt-2">Decided</div>
            <div class="col-6 mt-2">{{ optional($application->decided_at ?? $application->approved_at ?? $application->rejected_at)->format('Y-m-d H:i') ?? '—' }}</div>

            <div class="col-6 text-muted mt-2">Assigned officer</div>
            <div class="col-6 mt-2">{{ $application->assignedOfficer?->name ?? '—' }}</div>

            <div class="col-6 text-muted mt-2">Locked</div>
            <div class="col-6 mt-2">
              @if($application->locked_at)
                <span class="badge bg-warning text-dark">Yes</span>
                <span class="small text-muted">by {{ $application->lockedBy?->name ?? 'N/A' }}</span>
              @else
                <span class="badge bg-success">No</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Attachments</h6>
            <span class="text-muted small">{{ $application->documents?->count() ?? 0 }} files</span>
          </div>

          <div class="mt-3">
            @forelse($application->documents as $doc)
              <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                  <div class="fw-semibold">{{ $doc->document_type ?? $doc->doc_type }}</div>
                  <div class="text-muted small">{{ $doc->original_name }}
                    @if($doc->size)
                      • {{ number_format($doc->size/1024, 1) }} KB
                    @endif
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <a class="btn btn-outline-primary btn-sm" target="_blank" href="{{ $doc->url }}">
                    <i class="ri-eye-line"></i>
                  </a>
                </div>
              </div>
            @empty
              <div class="text-muted">No attachments found.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Activity timeline</h6>
            <span class="text-muted small">Latest first</span>
          </div>
          <div class="mt-3" style="max-height: 520px; overflow:auto;">
            @forelse($timeline as $item)
              <div class="py-2 border-bottom">
                <div class="d-flex justify-content-between">
                  <div class="fw-semibold">{{ ucwords(str_replace('_',' ', $item['action'] ?? '')) }}</div>
                  <div class="text-muted small">{{ optional($item['time'])->format('Y-m-d H:i') }}</div>
                </div>
                <div class="text-muted small">{{ $item['actor'] ?? 'System' }} • {{ $item['type'] }}</div>
                @if(!empty($item['meta']))
                  <div class="small mt-1">{{ $item['meta'] }}</div>
                @endif
              </div>
            @empty
              <div class="text-muted">No activity logs found.</div>
            @endforelse
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">
          <h6 class="fw-bold">Admin actions</h6>
          <div class="text-muted small mb-2">These actions are logged to <code>application_audit_logs</code>.</div>

          <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('staff.it.application.unlock', $application) }}">
            @csrf
            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (required for audit)" style="max-width: 380px;" />
            <button class="btn btn-sm btn-outline-warning" type="submit"><i class="ri-lock-unlock-line me-1"></i>Unlock</button>
          </form>

          <form class="d-flex flex-wrap gap-2 mt-2" method="POST" action="{{ route('staff.it.application.reset', $application) }}">
            @csrf
            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason (required for audit)" style="max-width: 380px;" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="ri-refresh-line me-1"></i>Reset to Draft</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
