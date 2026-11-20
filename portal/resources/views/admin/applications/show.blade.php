@extends('layouts.portal')

@section('title', 'Application Details')

@section('content')
@php
  $isAcc = ($application->application_type === 'accreditation');
@endphp

<div class="zmc-dashboard-wrapper" style="font-family:'Roboto', sans-serif; color:#334155;">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h1 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Application: {{ $application->reference ?? 'N/A' }}</h1>
      <div class="text-muted mt-1" style="font-size:13px;">Read-only view for admin/director oversight.</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-dark">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-user-3-line me-2"></i>Applicant</div>
        </div>
        <div class="card-body">
          <div class="fw-bold">{{ $application->applicant?->name ?? 'Applicant' }}</div>
          <div class="text-muted">{{ $application->applicant?->email }}</div>
          <div class="mt-3">
            <div class="text-muted small">Type</div>
            <div class="fw-semibold">{{ ucfirst($application->application_type ?? 'Unknown') }}</div>
          </div>
          <div class="mt-3">
            <div class="text-muted small">Status</div>
            <div><span class="badge bg-secondary">{{ ucwords(str_replace('_',' ', $application->status ?? 'unknown')) }}</span></div>
          </div>
          <div class="mt-3">
            <div class="text-muted small">Submitted</div>
            <div class="fw-semibold">{{ $application->submitted_at?->toDayDateTimeString() ?? $application->created_at?->toDayDateTimeString() }}</div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-attachment-2 me-2"></i>Documents</div>
        </div>
        <div class="card-body">
          @forelse($application->documents as $doc)
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
              <div>
                <div class="fw-semibold">{{ ucwords(str_replace('_',' ', $doc->document_type ?? 'document')) }}</div>
                <div class="text-muted small">{{ $doc->created_at?->diffForHumans() }}</div>
              </div>
              @if($doc->path)
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ \Illuminate\Support\Facades\Storage::url($doc->path) }}">
                  <i class="ri-external-link-line"></i>
                </a>
              @endif
            </div>
          @empty
            <div class="text-muted">No documents uploaded.</div>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-file-text-line me-2"></i>Form Data</div>
        </div>
        <div class="card-body">
          @php $data = $application->form_data ?? []; @endphp
          @if(empty($data))
            <div class="text-muted">No form data captured.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                @foreach($data as $k => $v)
                  <tr>
                    <td style="width:35%" class="text-muted fw-semibold">{{ ucwords(str_replace('_',' ', $k)) }}</td>
                    <td>
                      @if(is_array($v))
                        <pre class="mb-0" style="white-space:pre-wrap;">{{ json_encode($v, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                      @else
                        {{ (string)$v }}
                      @endif
                    </td>
                  </tr>
                @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      @include('staff.partials.application_comments', ['application' => $application])

      <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white border-0 py-3">
          <div class="fw-bold"><i class="ri-message-2-line me-2"></i>Messages</div>
        </div>
        <div class="card-body">
          @forelse($application->messages as $m)
            <div class="border rounded p-3 mb-2">
              <div class="d-flex justify-content-between">
                <div class="fw-bold">{{ $m->fromUser?->name ?? 'User' }} <span class="text-muted">→</span> {{ $m->toUser?->name ?? 'User' }}</div>
                <div class="text-muted small">{{ $m->sent_at?->diffForHumans() ?? $m->created_at?->diffForHumans() }}</div>
              </div>
              <div class="mt-2">{{ $m->body }}</div>
            </div>
          @empty
            <div class="text-muted">No messages.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
