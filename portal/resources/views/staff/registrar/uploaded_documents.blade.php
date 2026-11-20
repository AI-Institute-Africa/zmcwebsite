@extends('layouts.portal')

@section('title', $title ?? 'Uploaded Documents')

@section('content')
@include('staff.partials.dashboard_theme')
<div class="registrar-theme">
<div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
  <style>
    .docs-hero{position:relative;overflow:hidden;padding:26px 30px;border-radius:22px;background:linear-gradient(115deg,#123f35,#1f6f57);color:#fff;box-shadow:0 16px 34px rgba(20,83,65,.15);margin-bottom:22px}.docs-hero:after{content:"";position:absolute;right:-55px;top:-105px;width:230px;height:230px;border-radius:50%;background:rgba(242,201,76,.16)}.docs-hero>*{position:relative;z-index:1}.docs-hero h1{font-size:clamp(1.55rem,2.4vw,2.1rem);font-weight:850;margin:0;letter-spacing:-.03em}.docs-hero p{color:rgba(255,255,255,.76);margin:7px 0 0;font-size:.9rem}.docs-hero .hero-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:18px}.docs-hero .btn{border-radius:10px;font-weight:750}.docs-summary{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}.docs-summary span{border:1px solid #e3ebe7;background:#f8fbf9;border-radius:999px;padding:7px 12px;font-size:.76rem;color:#5e706a;font-weight:700}.docs-filter{border:1px solid #e3ece7!important;border-radius:18px!important;background:linear-gradient(135deg,#f8fbf9,#f2f7f4)!important;padding:18px!important;box-shadow:none!important}.docs-filter .form-control{height:40px;border-radius:10px;border-color:#dce7e1}.docs-filter .btn{height:40px;border-radius:10px;font-weight:750}.docs-tabs .btn{border-radius:999px;font-weight:750;padding:.48rem .85rem}.docs-accordion .accordion-item{border:1px solid #e5ede9!important;border-radius:16px!important;box-shadow:0 7px 20px rgba(20,58,46,.05)!important;margin-bottom:10px!important;overflow:hidden}.docs-accordion .accordion-header{display:flex;align-items:stretch;background:#fff}.docs-accordion .docs-row{display:flex;align-items:stretch;width:100%}.docs-accordion .accordion-button{background:#fff;padding:17px 18px;flex:1;min-width:0}.docs-accordion .accordion-button:not(.collapsed){color:#17352c;background:#f5faf7;box-shadow:none}.docs-accordion .accordion-button:focus{box-shadow:none}.docs-accordion .doc-actions{display:flex;align-items:center;padding:0 15px 0 6px;background:#fff}.docs-accordion .doc-count{border-radius:999px;background:#e8f5ed;color:#176a4f;font-size:.7rem;padding:.42em .7em;font-weight:800}.docs-accordion .accordion-body{background:#f8fbf9!important;padding:18px}.docs-accordion .table{background:#fff;border-radius:12px;overflow:hidden}.docs-accordion .table thead th{font-size:.67rem;text-transform:uppercase;letter-spacing:.05em;color:#50645f;background:#f0f6f2}.docs-accordion .badge{border-radius:999px}.docs-accordion .btn{border-radius:9px;font-weight:700}.docs-empty{border:1px dashed #cbdcd3;border-radius:16px;background:#fbfdfc;padding:44px 20px;text-align:center}@media(max-width:600px){.docs-hero{padding:22px 20px}.docs-row{display:block!important}.docs-actions{padding:0 15px 12px!important;justify-content:flex-end}}
  </style>
  <div class="docs-hero"><div class="text-uppercase small fw-bold" style="letter-spacing:.14em;opacity:.72"><i class="ri-folder-open-line me-1"></i> Records workspace</div><h1>{{ $title ?? 'Uploaded Documents' }}</h1><p>Review applicant files by application, open individual documents, or download a complete ZIP archive.</p><div class="hero-actions"><button type="button" onclick="window.print()" class="btn btn-light"><i class="ri-printer-line me-1"></i> Export PDF</button><a href="{{ route('staff.registrar.dashboard') }}" class="btn btn-outline-light"><i class="ri-arrow-left-line me-1"></i> Dashboard</a></div></div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger d-flex align-items-start gap-2">
      <i class="ri-error-warning-line" style="font-size: var(--font-size-lg); line-height: 1;"></i>
      <div>{{ session('error') }}</div>
    </div>
  @endif

  @php $activeType = request('application_type'); @endphp
  <div class="docs-summary"><span><i class="ri-file-list-3-line me-1"></i>{{ $applications->total() }} applications</span><span><i class="ri-shield-check-line me-1"></i>Grouped by applicant</span><span><i class="ri-download-cloud-2-line me-1"></i>ZIP downloads available</span></div>
  <div class="docs-tabs d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm {{ $activeType ? 'btn-outline-dark' : 'btn-dark' }}" href="{{ request()->fullUrlWithQuery(['application_type' => null]) }}">All</a>
    <a class="btn btn-sm {{ $activeType==='accreditation' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ request()->fullUrlWithQuery(['application_type' => 'accreditation']) }}">Accreditation Documents</a>
    <a class="btn btn-sm {{ $activeType==='registration' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ request()->fullUrlWithQuery(['application_type' => 'registration']) }}">Media House Documents</a>
  </div>

  <div class="docs-filter card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold">From</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small fw-bold">To</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" />
        </div>
        <input type="hidden" name="application_type" value="{{ request('application_type') }}" />
        <div class="col-12 col-md-3 d-flex gap-2">
          <button class="btn btn-dark w-100"><i class="ri-filter-3-line me-1"></i>Apply</button>
          <a class="btn btn-outline-secondary w-100" href="{{ url()->current() }}">Reset</a>
        </div>
      </form>
    </div>
  </div>

  <div class="docs-accordion accordion" id="docsAccordion">
    @forelse($applications as $idx => $app)
      @php
        $accId = 'acc_'.$app->id;
      @endphp
      <div class="accordion-item">
        <div class="accordion-header" id="heading{{ $accId }}">
          <div class="docs-row">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $accId }}" aria-expanded="false" aria-controls="collapse{{ $accId }}">
              <div class="text-start">
                <div class="fw-bold fs-6 text-dark">{{ $app->applicant?->name ?? '—' }} <span class="text-muted fw-normal">({{ $app->applicant?->email ?? '—' }})</span></div>
                <div class="small text-muted mt-1">Ref: <span class="fw-semibold">{{ $app->reference }}</span> • {{ $app->applicationTypeLabel() }} • Submitted: {{ optional($app->created_at)->format('d M Y H:i') }}</div>
              </div>
            </button>
            <div class="doc-actions">
              <span class="doc-count me-2">{{ $app->documents->count() }} documents</span>
              @if($app->documents->count() > 0)
                <a href="{{ route('staff.registrar.applications.zip-download', $app) }}" class="btn btn-sm btn-outline-success" title="Download all documents as ZIP" aria-label="Download all documents as ZIP"><i class="ri-file-zip-line"></i></a>
              @endif
            </div>
          </div>
        </div>
        <div id="collapse{{ $accId }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $accId }}" data-bs-parent="#docsAccordion">
          <div class="accordion-body bg-light-subtle">
            @if($app->documents->count() > 0)
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="m-0 fw-bold text-secondary"><i class="ri-attachment-line me-1"></i> Uploaded Files</h2>
                <a href="{{ route('staff.registrar.applications.zip-download', $app) }}" class="btn btn-sm btn-success px-3 shadow-xs">
                  <i class="ri-file-zip-line me-1"></i> Download All as ZIP
                </a>
              </div>
              <div class="table-responsive bg-white rounded-3 border">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3 py-2">Document</th>
                      <th class="py-2">Original Name</th>
                      <th class="py-2">Status</th>
                      <th class="text-end pe-3 py-2">Open</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($app->documents as $doc)
                      <tr>
                        <td class="fw-semibold ps-3 py-2">{{ $doc->document_type }}</td>
                        <td class="small text-muted py-2">{{ $doc->original_name ?? '—' }}</td>
                        <td class="py-2">
                          <span class="badge text-capitalize {{ $doc->status === 'accepted' ? 'bg-success-subtle text-success' : ($doc->status === 'rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning') }}">
                            {{ $doc->status ?? 'pending' }}
                          </span>
                        </td>
                        <td class="text-end pe-3 py-2">
                          <a href="{{ $doc->url }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Open document" aria-label="Open document">
                            <i class="ri-external-link-line"></i>
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <div class="text-center py-4 text-muted">
                <i class="ri-file-warning-line display-6 d-block mb-2"></i>
                No documents have been uploaded for this application.
              </div>
            @endif
          </div>
        </div>
      </div>
    @empty
      <div class="docs-empty text-muted">
        <i class="ri-folder-open-line display-5 d-block mb-2 text-muted" style="opacity: 0.5;"></i>
        No documents found for the selected filters.
      </div>
    @endforelse
  </div>

  <div class="mt-3">{{ $applications->links() }}</div>
</div>
</div>
@endsection
