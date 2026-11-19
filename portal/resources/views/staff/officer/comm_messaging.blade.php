@extends('layouts.portal')
@section('title', 'Bulk Messaging')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Bulk Messaging</h4>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i>Send an in-portal message to a group of applicants. Messages appear in
          each applicant's portal inbox against their application.
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.officer.dashboard') }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-dashboard-3-line me-1"></i>Dashboard
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
      <div class="col-12 col-xl-7">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Compose message</h6>
            <form method="POST" action="{{ route('staff.officer.comm.messaging.send') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label small fw-bold">Recipients</label>
                <select name="audience" class="form-select form-select-sm" required>
                  @foreach($audiences as $value => $label)
                    <option value="{{ $value }}" {{ old('audience') === $value ? 'selected' : '' }}>
                      {{ $label }} ({{ number_format($counts[$value] ?? 0) }})
                    </option>
                  @endforeach
                </select>
                <div class="form-text">
                  Recipients are de-duplicated by applicant account. Only applicants linked to an application are messaged.
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Subject <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" maxlength="150" class="form-control form-control-sm"
                  placeholder="e.g. Outstanding document verification">
              </div>
              <div class="mb-3">
                <label class="form-label small fw-bold">Message</label>
                <textarea name="body" rows="6" maxlength="5000" class="form-control form-control-sm" required
                  placeholder="Type the message applicants will receive...">{{ old('body') }}</textarea>
              </div>
              <button type="submit" class="btn btn-dark btn-sm px-4"
                onclick="return confirm('Send this message to the selected recipient group?');">
                <i class="ri-send-plane-2-line me-1"></i>Send message
              </button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-5">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <h6 class="fw-bold mb-3">Recipient groups</h6>
            <ul class="list-group list-group-flush">
              @foreach($audiences as $value => $label)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                  <span class="small">{{ $label }}</span>
                  <span class="badge bg-dark rounded-pill">{{ number_format($counts[$value] ?? 0) }}</span>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Recently sent by you</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Application</th>
                <th>Applicant</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Sent</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recent as $message)
                <tr>
                  <td class="fw-semibold">
                    {{ $message->application->reference ?? ('#' . $message->application_id) }}
                  </td>
                  <td>{{ $message->application->applicant->name ?? '—' }}</td>
                  <td>{{ $message->subject ?: '—' }}</td>
                  <td class="text-muted">{{ \Illuminate\Support\Str::limit($message->body, 70) }}</td>
                  <td>{{ optional($message->sent_at)->format('Y-m-d H:i') ?: '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">You have not sent any messages yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
