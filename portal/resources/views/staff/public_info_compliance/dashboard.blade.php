@extends('layouts.portal')
@section('title','Public Information Compliance Management')
@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">
        <i class="ri-shield-check-line me-2" style="color:var(--zmc-accent);"></i>Public Information Compliance Management
      </h1>
      <div class="text-muted mt-1" style="font-size:13px;">
        Complaints are handled in the Complaints Handling System. Use FOIA Appeals Management for public information appeals.
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if($errors ?? false)
    @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  @endif

  {{-- Statistics Cards with Doughnut Charts --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="zmc-card text-center">
        <div class="mb-2" style="width:100px;height:100px;margin:0 auto;">
          <canvas id="totalChart"></canvas>
        </div>
        <h2 class="fw-bold m-0" style="color:var(--zmc-primary);">{{ number_format($stats['total']) }}</h2>
        <p class="text-muted small m-0">Total Records</p>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="zmc-card text-center">
        <div class="mb-2" style="width:100px;height:100px;margin:0 auto;">
          <canvas id="pendingChart"></canvas>
        </div>
        <h3 class="fw-bold m-0 text-warning">{{ number_format($stats['pending']) }}</h3>
        <p class="text-muted small m-0">Pending</p>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="zmc-card text-center">
        <div class="mb-2" style="width:100px;height:100px;margin:0 auto;">
          <canvas id="resolvedChart"></canvas>
        </div>
        <h3 class="fw-bold m-0 text-success">{{ number_format($stats['resolved']) }}</h3>
        <p class="text-muted small m-0">Resolved</p>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="zmc-card text-center h-100 d-flex flex-column justify-content-center">
        <h4 class="fw-bold m-0" style="color:var(--zmc-primary);">Public Information</h4>
        <p class="text-muted small m-0 mt-2">Compliance Management</p>
      </div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="zmc-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
      <div class="col-md-3">
        <!-- Removed Type filter since it's only complaints -->
      </div>
      <div class="col-md-3">
        <label class="form-label zmc-lbl">Status</label>
        <select name="status" class="form-select zmc-input">
          <option value="">All</option>
          <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-dark w-100">
          <i class="ri-filter-3-line me-1"></i>Filter
        </button>
      </div>
      <div class="col-md-3">
        <a href="{{ route('staff.public_info_compliance.dashboard') }}" class="btn btn-light w-100">Reset</a>
      </div>
    </form>
  </div>

  {{-- Complaints Table --}}
  <div class="zmc-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Submitted</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($complaints as $c)
            <tr>
              <td>
                <div class="fw-bold">{{ $c->name }}</div>
                <div class="small text-muted">{{ $c->email }}</div>
              </td>
              <td>{{ Str::limit($c->subject, 50) }}</td>
              <td>
                <span class="badge rounded-pill bg-{{ $c->status === 'resolved' ? 'success' : 'warning' }}">
                  {{ ucfirst($c->status) }}
                </span>
              </td>
              <td class="small">{{ $c->created_at->format('d M Y H:i') }}</td>
              <td class="text-end">
                <button class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewComplaint{{ $c->id }}">
                  <i class="ri-eye-line"></i>
                </button>
                @hasanyrole('public_info_compliance|super_admin|director')
                  <button class="btn btn-sm zmc-icon-btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editComplaint{{ $c->id }}">
                    <i class="ri-edit-line"></i>
                  </button>
                  <form method="POST" action="{{ route('staff.public_info_compliance.complaints.destroy', $c) }}" class="d-inline">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm zmc-icon-btn btn-outline-danger" onclick="return confirm('Delete this record?')">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                @endhasanyrole
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No complaints found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $complaints->links() }}</div>
  </div>
</div>

{{-- Create Complaint/Appeal Modal --}}
@hasanyrole('public_info_compliance|super_admin|director')
<div class="modal fade" id="createComplaint" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="POST" action="{{ route('staff.public_info_compliance.complaints.store') }}">
      @csrf
      <div class="modal-header zmc-modal-header">
        <div class="zmc-modal-title">New Complaint</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <!-- Type is fixed to Complaint -->
          </div>
          <div class="col-md-6">
            <label class="form-label zmc-lbl">Status</label>
            <select name="status" class="form-select zmc-input">
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label zmc-lbl">Name</label>
            <input type="text" name="name" class="form-control zmc-input" required>
          </div>
          <div class="col-md-6">
            <label class="form-label zmc-lbl">Email</label>
            <input type="email" name="email" class="form-control zmc-input" required>
          </div>
          <div class="col-md-6">
            <label class="form-label zmc-lbl">Phone (optional)</label>
            <input type="text" name="phone" class="form-control zmc-input">
          </div>
          <div class="col-12">
            <label class="form-label zmc-lbl">Subject</label>
            <input type="text" name="subject" class="form-control zmc-input" required>
          </div>
          <div class="col-12">
            <label class="form-label zmc-lbl">Message</label>
            <textarea name="message" class="form-control zmc-input" rows="4" required></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer zmc-modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-dark">Save</button>
      </div>
    </form>
  </div>
</div>
@endhasanyrole

{{-- View & Edit Modals --}}
@foreach($complaints as $c)
  {{-- View Modal --}}
  <div class="modal fade" id="viewComplaint{{ $c->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header zmc-modal-header">
          <div class="zmc-modal-title">View {{ ucfirst($c->type) }}</div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <!-- Type -->
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Status</label>
              <p class="form-control-plaintext">
                <span class="badge bg-{{ $c->status === 'resolved' ? 'success' : 'warning' }}">{{ ucfirst($c->status) }}</span>
              </p>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Name</label>
              <p class="form-control-plaintext fw-bold">{{ $c->name }}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Email</label>
              <p class="form-control-plaintext">{{ $c->email }}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Phone</label>
              <p class="form-control-plaintext">{{ $c->phone ?? '—' }}</p>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Submitted</label>
              <p class="form-control-plaintext">{{ $c->created_at->format('d M Y H:i') }}</p>
            </div>
            <div class="col-12">
              <label class="form-label zmc-lbl">Subject</label>
              <p class="form-control-plaintext fw-bold">{{ $c->subject }}</p>
            </div>
            <div class="col-12">
              <label class="form-label zmc-lbl">Message</label>
              <div class="p-3 bg-light rounded">{{ $c->message }}</div>
            </div>
            @if($c->resolution_notes)
              <div class="col-12">
                <label class="form-label zmc-lbl">Resolution Notes</label>
                <div class="p-3 bg-success-subtle rounded">{{ $c->resolution_notes }}</div>
              </div>
            @endif
          </div>
        </div>
        <div class="modal-footer zmc-modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Edit Modal --}}
  @hasanyrole('public_info_compliance|super_admin|director')
  <div class="modal fade" id="editComplaint{{ $c->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <form class="modal-content" method="POST" action="{{ route('staff.public_info_compliance.complaints.update', $c) }}">
        @csrf @method('PUT')
        <div class="modal-header zmc-modal-header">
          <div class="zmc-modal-title">Edit {{ ucfirst($c->type) }}</div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <!-- Type -->
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Status</label>
              <select name="status" class="form-select zmc-input" required>
                <option value="pending" {{ $c->status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="resolved" {{ $c->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Name</label>
              <input type="text" name="name" class="form-control zmc-input" value="{{ $c->name }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Email</label>
              <input type="email" name="email" class="form-control zmc-input" value="{{ $c->email }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label zmc-lbl">Phone</label>
              <input type="text" name="phone" class="form-control zmc-input" value="{{ $c->phone }}">
            </div>
            <div class="col-12">
              <label class="form-label zmc-lbl">Subject</label>
              <input type="text" name="subject" class="form-control zmc-input" value="{{ $c->subject }}" required>
            </div>
            <div class="col-12">
              <label class="form-label zmc-lbl">Message</label>
              <textarea name="message" class="form-control zmc-input" rows="4" required>{{ $c->message }}</textarea>
            </div>
            <div class="col-12">
              <label class="form-label zmc-lbl">Resolution Notes</label>
              <textarea name="resolution_notes" class="form-control zmc-input" rows="3" placeholder="Add notes when resolving...">{{ $c->resolution_notes }}</textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer zmc-modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
  @endhasanyrole
@endforeach

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Doughnut charts for statistics
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
      legend: { display: false },
      tooltip: { enabled: false }
    }
  };

  // Total Records Chart (filled ring)
  new Chart(document.getElementById('totalChart'), {
    type: 'doughnut',
    data: {
      labels: ['Total'],
      datasets: [{
        data: [{{ $stats['total'] }}, {{ $stats['total'] > 0 ? 0 : 1 }}],
        backgroundColor: ['#1e293b', '#e2e8f0'],
        borderWidth: 0
      }]
    },
    options: chartOptions
  });

  // Pending Chart
  new Chart(document.getElementById('pendingChart'), {
    type: 'doughnut',
    data: {
      labels: ['Pending', 'Others'],
      datasets: [{
        data: [{{ $stats['pending'] }}, {{ $stats['total'] - $stats['pending'] }}],
        backgroundColor: ['#f59e0b', '#e2e8f0'],
        borderWidth: 0
      }]
    },
    options: chartOptions
  });

  // Resolved Chart
  new Chart(document.getElementById('resolvedChart'), {
    type: 'doughnut',
    data: {
      labels: ['Resolved', 'Others'],
      datasets: [{
        data: [{{ $stats['resolved'] }}, {{ $stats['total'] - $stats['resolved'] }}],
        backgroundColor: ['#10b981', '#e2e8f0'],
        borderWidth: 0
      }]
    },
    options: chartOptions
  });

  // Removed Type Chart
</script>
@endpush
