@extends('layouts.portal')
@section('title', 'Reminders')

@section('content')
@include('staff.partials.dashboard_theme')
@include('staff.partials.dashboard_theme')
<div class="registrar-theme">
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
        <i class="ri-alarm-line me-2"></i> Reminders
      </h1>
      <div class="text-muted mt-1" style="font-size:13px;">
        Create and manage reminders for practitioners and media houses.
      </div>
    </div>
    <div class="d-flex gap-2">
      <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createReminderModal">
        <i class="ri-add-line me-1"></i> New Reminder
      </button>
      <a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-outline-dark btn-sm">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="zmc-card">
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr class="text-muted small">
            <th>#</th>
            <th>Target Type</th>
            <th>Target ID</th>
            <th>Reminder Type</th>
            <th>Message</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Acknowledged</th>
          </tr>
        </thead>
        <tbody>
        @forelse($reminders as $i => $reminder)
          <tr>
            <td class="small text-muted">{{ $reminders->firstItem() + $i }}</td>
            <td class="small fw-bold">{{ ucfirst(str_replace('_', ' ', $reminder->target_type)) }}</td>
            <td class="small">{{ $reminder->target_id }}</td>
            <td><span class="badge bg-info rounded-pill">{{ $reminder->reminder_type }}</span></td>
            <td class="small" style="max-width:300px;">{{ \Illuminate\Support\Str::limit($reminder->message, 80) }}</td>
            <td class="small">{{ $reminder->creator?->name ?? '—' }}</td>
            <td class="small text-muted">{{ $reminder->created_at ? $reminder->created_at->format('d M Y H:i') : '—' }}</td>
            <td class="small">
              @if($reminder->acknowledged_at)
                <span class="text-success"><i class="ri-check-double-line"></i> {{ $reminder->acknowledged_at->format('d M Y H:i') }}</span>
              @else
                <span class="text-warning">Pending</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted small text-center">No reminders found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($reminders, 'links'))
      <div class="mt-3">{{ $reminders->links() }}</div>
    @endif
  </div>

</div>

<div class="modal fade" id="createReminderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="{{ route('staff.registrar.reminders.store') }}">
      @csrf
      <div class="modal-header">
        <h2 class="modal-title fw-bold"><i class="ri-alarm-line me-2"></i> Create Reminder</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-bold small">Target Type <span class="text-danger">*</span></label>
          <select name="target_type" class="form-select form-select-sm" required>
            <option value="">-- Select --</option>
            <option value="user">Practitioner (User)</option>
            <option value="media_house">Media House</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold small">Target ID <span class="text-danger">*</span></label>
          <input type="number" name="target_id" class="form-control form-control-sm" required min="1" placeholder="Enter user or media house ID">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold small">Reminder Type <span class="text-danger">*</span></label>
          <select name="reminder_type" class="form-select form-select-sm" required>
            <option value="">-- Select --</option>
            <option value="renewal_due">Renewal Due</option>
            <option value="payment_pending">Payment Pending</option>
            <option value="document_required">Document Required</option>
            <option value="general">General</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold small">Message <span class="text-danger">*</span></label>
          <textarea name="message" class="form-control form-control-sm" rows="4" required placeholder="Enter reminder message..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light btn-sm fw-bold" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm fw-bold">
          <i class="ri-send-plane-line me-1"></i> Create Reminder
        </button>
      </div>
    </form>
  </div>
</div>
</div>
@endsection
