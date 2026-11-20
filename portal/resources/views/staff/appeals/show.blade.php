@extends('layouts.portal')
@section('title', 'Appeal Details')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Inter', sans-serif;">
  
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <a href="{{ route('staff.appeals.index') }}" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="ri-arrow-left-line me-1"></i>Back to Appeals</a>
      <h1 class="fw-bold m-0" style="color:#0f172a;">Appeal: {{ $appeal->reference_number ?? 'N/A' }}</h1>
      <div class="text-muted small">Submitted on {{ $appeal->created_at->format('d M Y, H:i') }}</div>
    </div>
    <div class="d-flex gap-2">
      <span class="badge bg-secondary fs-6">{{ strtoupper(str_replace('_', ' ', $appeal->status)) }}</span>
      @if($appeal->payment_status === 'paid')
        <span class="badge bg-success fs-6">PAID</span>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success"><i class="ri-check-line me-2"></i>{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger"><i class="ri-error-warning-line me-2"></i>{{ session('error') }}</div>
  @endif

  <div class="row g-4">
    <div class="col-lg-8">
      {{-- Applicant Info --}}
      <div class="zmc-card mb-4">
        <h2 class="fw-bold mb-3 border-bottom pb-2">Applicant Information</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="text-muted small">Full Name</div>
            <div class="fw-bold">{{ $appeal->full_name }}</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Email Address</div>
            <div class="fw-bold">{{ $appeal->email }}</div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small">Phone Number</div>
            <div class="fw-bold">{{ $appeal->phone ?? 'N/A' }}</div>
          </div>
        </div>
      </div>

      {{-- Appeal Details --}}
      <div class="zmc-card mb-4">
        <h3 class="fw-bold mb-3 border-bottom pb-2">Appeal Details</h3>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="text-muted small">Category</div>
            <div class="fw-bold text-primary">{{ $appeal->category->name ?? 'Uncategorized' }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted small">Title/Subject</div>
            <div class="fw-bold">{{ $appeal->subject }}</div>
          </div>
          <div class="col-12">
            <div class="text-muted small mb-1">Appeal Description</div>
            <div class="p-3 bg-light rounded" style="white-space: pre-wrap;">{{ $appeal->message }}</div>
          </div>
          
          @if($appeal->attachment_path)
          <div class="col-12">
            <div class="text-muted small mb-1">Uploaded Document</div>
            <a href="{{ Storage::url($appeal->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-dark">
              <i class="ri-file-download-line me-1"></i> {{ $appeal->attachment_original_name ?? 'Download Attachment' }}
            </a>
          </div>
          @endif
        </div>
      </div>

      {{-- Communications Tab --}}
      <div class="zmc-card mb-4">
        <h4 class="fw-bold mb-3 border-bottom pb-2">Communication History</h4>
        @if($appeal->communications->count() > 0)
          <div class="list-group list-group-flush">
            @foreach($appeal->communications as $comm)
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between">
                  <div class="fw-bold text-dark">{{ $comm->subject }}</div>
                  <div class="small text-muted">{{ $comm->sent_at->format('d M Y, H:i') }}</div>
                </div>
                <div class="small text-muted mb-2">Sent by: {{ $comm->sender->name ?? 'System' }} | Status: <span class="badge bg-light text-dark">{{ $comm->delivery_status }}</span></div>
                <div class="p-2 bg-light rounded small" style="white-space: pre-wrap;">{{ $comm->body }}</div>
              </div>
            @endforeach
          </div>
        @else
          <div class="text-muted text-center py-3">No communications sent yet.</div>
        @endif
      </div>
    </div>

    {{-- Officer Actions Sidebar --}}
    <div class="col-lg-4">
      
      {{-- Status & Assignment --}}
      <div class="zmc-card mb-4 border-top border-4 border-primary">
        <h5 class="fw-bold mb-3">Officer Actions</h5>
        
        <form method="POST" action="{{ route('staff.appeals.assign', $appeal) }}" class="mb-3">
          @csrf @method('PATCH')
          <label class="form-label zmc-lbl small">Assigned Officer</label>
          <div class="input-group input-group-sm">
            <select name="officer_id" class="form-select zmc-input">
              <option value="">Unassigned</option>
              @foreach($officers as $officer)
                <option value="{{ $officer->id }}" {{ $appeal->handled_by == $officer->id ? 'selected' : '' }}>{{ $officer->name }}</option>
              @endforeach
            </select>
            <button class="btn btn-dark" type="submit">Assign</button>
          </div>
        </form>

        <form method="POST" action="{{ route('staff.appeals.update_status', $appeal) }}" class="mb-3">
          @csrf @method('PATCH')
          <label class="form-label zmc-lbl small">Change Status</label>
          <div class="input-group input-group-sm">
            <select name="status" class="form-select zmc-input">
              <option value="open" {{ $appeal->status === 'open' ? 'selected' : '' }}>Submitted</option>
              <option value="in_progress" {{ $appeal->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
              <option value="under_review" {{ $appeal->status === 'under_review' ? 'selected' : '' }}>Under Review</option>
              <option value="awaiting_information" {{ $appeal->status === 'awaiting_information' ? 'selected' : '' }}>Awaiting Information</option>
              <option value="response_sent" {{ $appeal->status === 'response_sent' ? 'selected' : '' }}>Response Sent</option>
              <option value="closed" {{ $appeal->status === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
            <button class="btn btn-dark" type="submit">Update</button>
          </div>
        </form>
      </div>

      {{-- Payments Info --}}
      <div class="zmc-card mb-4 border-top border-4 border-success">
        <h6 class="fw-bold mb-3">Payment Info</h6>
        <div class="small">
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Status:</span>
            <span class="fw-bold">{{ strtoupper($appeal->payment_status ?? 'UNPAID') }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Paynow Ref:</span>
            <span class="fw-bold">{{ $appeal->payment_ref ?? 'N/A' }}</span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Amount:</span>
            <span class="fw-bold">{{ $appeal->payment_currency ?? 'USD' }} {{ number_format((float) ($appeal->payment_amount ?? 0), 2) }}</span>
          </div>
        </div>
        @if($appeal->appealPayments->isNotEmpty())
          <hr>
          <div class="small fw-bold mb-2">Transaction attempts</div>
          @foreach($appeal->appealPayments->sortByDesc('id') as $attempt)
            <div class="small d-flex justify-content-between gap-2 mb-1">
              <span>{{ $attempt->reference }} · {{ $attempt->currency }} {{ number_format((float) $attempt->amount, 2) }}</span>
              <span>{{ strtoupper($attempt->status) }}</span>
            </div>
          @endforeach
        @endif
      </div>

      {{-- Internal Notes --}}
      <div class="zmc-card mb-4 border-top border-4 border-warning">
        <h6 class="fw-bold mb-3">Internal Notes</h6>
        <form method="POST" action="{{ route('staff.appeals.add_note', $appeal) }}">
          @csrf
          <textarea name="internal_notes" class="form-control zmc-input mb-2" rows="4" placeholder="Add private notes...">{{ $appeal->internal_notes }}</textarea>
          <button type="submit" class="btn btn-sm btn-dark w-100">Save Notes</button>
        </form>
      </div>

      {{-- Send Response --}}
      <div class="zmc-card border-top border-4 border-info">
        <h6 class="fw-bold mb-3">Send Response to Applicant</h6>
        <form method="POST" action="{{ route('staff.appeals.communicate', $appeal) }}">
          @csrf
          <div class="mb-2">
            <input type="text" name="subject" class="form-control form-control-sm zmc-input" placeholder="Subject" value="Update on Appeal: {{ $appeal->reference_number }}" required>
          </div>
          <div class="mb-2">
            <textarea name="body" class="form-control zmc-input" rows="5" placeholder="Write email response..." required></textarea>
          </div>
          <button type="submit" class="btn btn-sm btn-info w-100 text-white"><i class="ri-mail-send-line me-1"></i>Send Email</button>
        </form>
      </div>

    </div>
  </div>

</div>
@endsection
