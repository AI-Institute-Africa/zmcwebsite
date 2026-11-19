@extends('layouts.portal')
@section('title', 'Oversight Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="fw-bold m-0">Oversight</h4>
    <div class="text-muted small">Read-only visibility across the entire process. Search + filters + view logs.</div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form class="row g-2" method="GET">
      <div class="col-md-3">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search ref / name">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">All statuses</option>
          @foreach($statuses as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_',' ', $s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <select name="region" class="form-select">
          <option value="">All regions</option>
          @foreach($regions as $r)
            <option value="{{ $r }}" @selected(request('region')===$r)>{{ $r }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 d-grid">
        <button class="btn btn-dark" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header fw-bold">All Applications (Read-only)</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 zmc-mini-table">
        @include('staff.partials._application_table_headers')
        <tbody>
        @forelse($applications as $i => $app)
          <tr>
            @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
            <td class="text-end">
              <a href="{{ route('staff.oversight.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View"><i class="ri-eye-line"></i></a>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center py-4 text-muted">No records found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="mt-3">
  {{ $applications->links() }}
</div>
@endsection
