@extends('layouts.portal')
@section('title', 'Bulk Review')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">Bulk Review</h1>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
          <i class="ri-information-line me-1"></i>Select several applications and request corrections or send the same
          message to all selected applicants in one step.
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('staff.officer.applications.index') }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-file-list-3-line me-1"></i>All Applications
        </a>
        <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3">
          <i class="ri-refresh-line me-1"></i>Refresh
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif
    @if(session('bulk_failures') && count(session('bulk_failures')))
      <div class="alert alert-warning py-2">
        <div class="fw-bold mb-1">Some applications were skipped:</div>
        <ul class="mb-0 small">
          @foreach(session('bulk_failures') as $failure)
            <li>{{ $failure }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="card shadow-sm mb-4 border-0">
      <div class="card-body py-3">
        <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label small fw-bold mb-1">Search</label>
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
              placeholder="Reference, applicant name or email">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-bold mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
              <option value="">All types</option>
              <option value="accreditation" {{ request('type') === 'accreditation' ? 'selected' : '' }}>Accreditation</option>
              <option value="registration" {{ request('type') === 'registration' ? 'selected' : '' }}>Registration</option>
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-bold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
              <option value="">All actionable</option>
              @foreach([
                  \App\Models\Application::SUBMITTED => 'Submitted',
                  \App\Models\Application::OFFICER_REVIEW => 'Officer review',
                  \App\Models\Application::RETURNED_TO_OFFICER => 'Returned to officer',
                  \App\Models\Application::REGISTRAR_FIX_REQUEST => 'Registrar fix request',
                  \App\Models\Application::SUBMITTED_WITH_APP_FEE => 'Submitted with app fee',
                  \App\Models\Application::AWAITING_ACCOUNTS_VERIFICATION => 'Awaiting accounts',
                ] as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-auto d-flex gap-2">
            <button class="btn btn-dark btn-sm px-3"><i class="ri-filter-3-line me-1"></i>Apply</button>
            <a href="{{ url()->current() }}" class="btn btn-white border btn-sm px-3">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <form method="POST" action="{{ route('staff.officer.applications.bulk-action') }}" id="bulkForm">
      @csrf
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="fw-semibold">
              {{ number_format($applications->total()) }} application(s) in this queue
            </div>
            <div class="text-muted" style="font-size: var(--font-size-xs);">
              <span id="selectedCount">0</span> selected
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:36px;">
                    <input type="checkbox" class="form-check-input" id="selectAll">
                  </th>
                  <th>Reference</th>
                  <th>Applicant</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Submitted</th>
                </tr>
              </thead>
              <tbody>
                @forelse($applications as $application)
                  <tr>
                    <td>
                      <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $application->id }}">
                    </td>
                    <td>
                      <a href="{{ route('staff.officer.applications.show', $application) }}"
                        class="fw-semibold text-decoration-none">
                        {{ $application->reference ?: '#' . $application->id }}
                      </a>
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $application->applicant->name ?? '—' }}</div>
                      <div class="text-muted" style="font-size: var(--font-size-xs);">{{ $application->applicant->email ?? '' }}</div>
                    </td>
                    <td>{{ ucfirst($application->application_type ?: '—') }}</td>
                    <td>@include('staff.partials.status-chip', ['status' => $application->status])</td>
                    <td>{{ optional($application->created_at)->format('Y-m-d H:i') ?: '—' }}</td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted py-4">No applications are awaiting bulk review.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          @if(method_exists($applications, 'links'))
            <div class="mt-3">{{ $applications->links() }}</div>
          @endif

          <hr>

          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label small fw-bold mb-1">Bulk action</label>
              <select name="action" class="form-select form-select-sm" required>
                <option value="request_correction">Request correction (returns to applicant)</option>
                <option value="send_message">Send message only</option>
              </select>
            </div>
            <div class="col-12 col-md-8">
              <label class="form-label small fw-bold mb-1">Message / correction notes</label>
              <textarea name="notes" rows="2" class="form-control form-control-sm" required maxlength="5000"
                placeholder="Explain what the applicant must correct or provide...">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-dark btn-sm px-4"
                onclick="return confirm('Apply this action to the selected applications?');">
                <i class="ri-play-list-2-line me-1"></i>Apply to selected
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

  @push('scripts')
    <script>
      (function () {
        const all = document.getElementById('selectAll');
        const checks = () => Array.from(document.querySelectorAll('.row-check'));
        const counter = document.getElementById('selectedCount');

        function refresh() {
          const selected = checks().filter(c => c.checked).length;
          counter.textContent = selected;
          if (all) {
            all.checked = selected > 0 && selected === checks().length;
          }
        }

        if (all) {
          all.addEventListener('change', function () {
            checks().forEach(c => { c.checked = all.checked; });
            refresh();
          });
        }
        checks().forEach(c => c.addEventListener('change', refresh));
        refresh();
      })();
    </script>
  @endpush
@endsection
