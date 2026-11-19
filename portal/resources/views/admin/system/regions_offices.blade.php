@extends('layouts.portal')

@section('title', 'Regions & Offices')

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="fw-bold mb-0">Regions & Offices</h4>
      <div class="text-muted" style="font-size:13px;">Maintain regional offices, schedules, and staff assignments.</div>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.regions.index') }}">Catalogue</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="row g-3">
    <div class="col-lg-8">
      <form method="POST" action="{{ route('admin.regions.offices') }}">
        @csrf
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
            <span>Offices</span>
            <span class="small text-muted">Assigned staff IDs are comma-separated</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="officeTable">
                <thead class="table-light"><tr><th>Office</th><th>Code</th><th>Region</th><th>Address</th><th>Phone</th><th>Email</th><th>Schedule</th><th>Assigned staff (IDs)</th><th>Public</th><th></th></tr></thead>
                <tbody>
                  @foreach(($cfg['offices'] ?? []) as $i => $o)
                    <tr>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][name]" value="{{ $o['name'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][code]" value="{{ $o['code'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][region]" value="{{ $o['region'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][address]" value="{{ $o['address'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][phone]" value="{{ $o['phone'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" type="email" name="offices[{{ $i }}][email]" value="{{ $o['email'] ?? '' }}" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][schedule]" value="{{ $o['schedule'] ?? '' }}" placeholder="e.g. Mon–Fri 08:00–16:30" /></td>
                      <td><input class="form-control form-control-sm" name="offices[{{ $i }}][assigned_user_ids]" value="{{ implode(',', $o['assigned_user_ids'] ?? []) }}" placeholder="e.g. 12,15" /></td>
                      <td class="text-center"><input type="checkbox" name="offices[{{ $i }}][is_published]" value="1" {{ !empty($o['is_published']) ? 'checked' : '' }} aria-label="Publish office" /></td>
                      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer bg-white d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOfficeRow()">Add office</button>
            <button class="btn btn-primary">Save Offices</button>
          </div>
        </div>
      </form>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold">Staff directory (IDs)</div>
        <div class="card-body">
          <div class="small text-muted mb-2">Use these IDs when assigning staff to an office.</div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Email</th></tr></thead>
              <tbody>
                @foreach($staff as $u)
                  <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Regional performance (applications)</div>
        <div class="card-body">
          <div class="small text-muted mb-2">Uses <code>applications.region</code> (if populated).</div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead class="table-light"><tr><th>Region</th><th class="text-end">Total</th></tr></thead>
              <tbody>
                @forelse($perf as $r)
                  <tr><td>{{ $r->region }}</td><td class="text-end">{{ $r->total }}</td></tr>
                @empty
                  <tr><td colspan="2" class="text-center text-muted py-3">No region data found.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function addOfficeRow(){
    const tbody = document.querySelector('#officeTable tbody');
    const i = tbody.querySelectorAll('tr').length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input class="form-control form-control-sm" name="offices[${i}][name]" required /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][code]" required /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][region]" /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][address]" /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][phone]" /></td>
      <td><input class="form-control form-control-sm" type="email" name="offices[${i}][email]" /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][schedule]" placeholder="Mon–Fri 08:00–16:30" /></td>
      <td><input class="form-control form-control-sm" name="offices[${i}][assigned_user_ids]" /></td>
      <td class="text-center"><input type="checkbox" name="offices[${i}][is_published]" value="1" checked aria-label="Publish office" /></td>
      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">Remove</button></td>
    `;
    tbody.appendChild(tr);
  }
</script>
@endsection
