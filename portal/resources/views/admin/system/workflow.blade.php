@extends('layouts.portal')

@section('title', 'Workflow & Routing')

@section('content')
<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Workflow & Routing</h1>
      <div class="text-muted mt-1" style="font-size:13px;">Read-only view of the end-to-end pipeline and allowed transitions.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('executive.dashboard') }}" class="btn btn-sm btn-outline-secondary"><i class="ri-arrow-left-line me-1"></i>Back</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12 col-xl-7">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
          <div class="fw-semibold">Pipeline</div>
          <div class="text-muted" style="font-size:12px;">Counts reflect current database state.</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Stage</th>
                  <th class="text-end">Count</th>
                </tr>
              </thead>
              <tbody>
                @foreach($pipeline as $row)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $row['label'] }}</div>
                      <div class="text-muted" style="font-size:12px;">{{ $row['key'] }}</div>
                    </td>
                    <td><span class="badge bg-light text-dark">{{ $row['stage'] }}</span></td>
                    <td class="text-end fw-bold">{{ $counts[$row['key']] ?? 0 }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
          <div class="fw-semibold">Allowed Transitions</div>
          <div class="text-muted" style="font-size:12px;">Used to prevent skipping stages (Officer → Accounts → Registrar → Production).</div>
        </div>
        <div class="card-body">
          @foreach($transitions as $from => $to)
            <div class="mb-3">
              <div class="fw-semibold">{{ $from }}</div>
              <div class="text-muted" style="font-size:12px;">Can move to:</div>
              <div class="d-flex flex-wrap gap-2 mt-2">
                @foreach($to as $dest)
                  <span class="badge bg-secondary">{{ $dest }}</span>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
          <div class="fw-semibold">Next enhancement</div>
          <div class="text-muted" style="font-size:13px;">If you want, we can make this page editable (status manager + routing rules + SLAs) and store it in the database.</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
