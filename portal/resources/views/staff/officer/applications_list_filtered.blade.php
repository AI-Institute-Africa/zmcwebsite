@extends('layouts.portal')

@section('title', $title ?? 'Applications')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <h1 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">{{ $title ?? 'Applications' }}
        </h1>
        <div class="text-muted mt-1" style="font-size: var(--font-size-base);"><i
            class="ri-information-line me-1"></i>Filter and search applications. Export the current filtered view as CSV.</div>
      </div>

      <div class="d-flex gap-2">
        <a href="{{ route('staff.officer.applications.export', array_merge(['list' => $list ?? 'all'], request()->query(), !empty($requestTypeFilter) ? ['request_type' => $requestTypeFilter] : [])) }}"
          class="btn btn-dark btn-sm px-3">
          <i class="ri-download-2-line me-1"></i> Export CSV
        </a>
        <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
          <i class="ri-refresh-line me-1"></i> Refresh
        </a>
      </div>
    </div>

    @php
      $activeYear = request('year', now()->year);
      $activeProcessingStatus = request('processing_status', 'all');
      $activeScope = request('scope');
      $activeType = request('application_type');
      $actionableStatuses = [
        \App\Models\Application::SUBMITTED,
        \App\Models\Application::OFFICER_REVIEW,
        \App\Models\Application::RETURNED_TO_OFFICER,
        \App\Models\Application::CORRECTION_REQUESTED,
      ];
    @endphp

    <div class="zmc-card mb-3">
      <form method="GET" action="{{ url()->current() }}">
        <div class="row g-3 align-items-end">

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold mb-1">Year</label>
            <select name="year" class="form-select form-select-sm">
              @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}" {{ $activeYear == $y ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold mb-1">Progress</label>
            <select name="processing_status" class="form-select form-select-sm">
              <option value="all" {{ $activeProcessingStatus === 'all' ? 'selected' : '' }}>All</option>
              <option value="processed" {{ $activeProcessingStatus === 'processed' ? 'selected' : '' }}>Processed</option>
              <option value="unprocessed" {{ $activeProcessingStatus === 'unprocessed' ? 'selected' : '' }}>Unprocessed
              </option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold mb-1">Scope</label>
            <select name="scope" class="form-select form-select-sm">
              <option value="" {{ !$activeScope ? 'selected' : '' }}>All</option>
              <option value="local" {{ $activeScope === 'local' ? 'selected' : '' }}>Local</option>
              <option value="foreign" {{ $activeScope === 'foreign' ? 'selected' : '' }}>Foreign</option>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label small fw-bold mb-1">Type</label>
            <select name="application_type" class="form-select form-select-sm">
              <option value="accreditation" {{ $activeType === 'accreditation' || !$activeType ? 'selected' : '' }}>
                Accreditations</option>
              <option value="registration" {{ $activeType === 'registration' ? 'selected' : '' }}>Registrations</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label small fw-bold mb-1">Search</label>
            <div class="input-group input-group-sm">
              <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                placeholder="Name, Accreditation / Registration No.">
              <button class="btn btn-dark" type="submit"><i class="ri-search-line me-1"></i>Filter</button>
              <a class="btn btn-outline-secondary" href="{{ url()->current() }}"><i class="ri-close-line"></i></a>
            </div>
          </div>

        </div>

        @if(($list ?? '') === 'rejected')
          @php $activeStatus = request('status'); @endphp
          <div class="d-flex gap-2 mt-3 pt-3 border-top">
            <label class="small fw-bold text-muted mb-0 me-1 align-self-center">Sub-filter:</label>
            <a class="btn btn-sm {{ !$activeStatus ? 'btn-dark' : 'btn-outline-dark' }}"
              href="{{ request()->fullUrlWithQuery(['status' => null]) }}">Both</a>
            <a class="btn btn-sm {{ $activeStatus === \App\Models\Application::OFFICER_REJECTED ? 'btn-warning' : 'btn-outline-warning' }}"
              href="{{ request()->fullUrlWithQuery(['status' => \App\Models\Application::OFFICER_REJECTED]) }}">Returned</a>
            <a class="btn btn-sm {{ $activeStatus === \App\Models\Application::RETURNED_TO_OFFICER ? 'btn-warning' : 'btn-outline-warning' }}"
              href="{{ request()->fullUrlWithQuery(['status' => \App\Models\Application::RETURNED_TO_OFFICER]) }}">Returned (Internal)</a>
          </div>
        @endif
      </form>

      <div class="mt-2">
        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-toggle="modal"
          data-bs-target="#advancedFiltersModal">
          <i class="ri-filter-3-line me-1"></i> Advanced Filters
        </button>
      </div>
    </div>

    <div class="zmc-card p-0 shadow-sm border-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 zmc-mini-table">
          @include('staff.partials._application_table_headers')
          <tbody>
            @forelse($applications as $i => $app)
              <tr>
                @include('staff.partials._application_table_row', ['app' => $app, 'i' => $i, 'applications' => $applications])
                <td class="text-end">
                  <a href="{{ route('staff.officer.applications.show', $app) }}" class="btn btn-sm zmc-icon-btn btn-outline-primary" data-bs-toggle="tooltip" title="View Application">
                    <i class="ri-eye-line"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-5">No applications found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-3">{{ $applications->links() }}</div>
  </div>

  {{-- Advanced Filters Modal --}}
  <div class="modal fade" id="advancedFiltersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title fw-bold">Advanced Filters</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="GET">
          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Sex</label>
                <select class="form-select" name="gender">
                  <option value="">All</option>
                  <option value="male" @selected(request('gender') === 'male')>Male</option>
                  <option value="female" @selected(request('gender') === 'female')>Female</option>
                  <option value="other" @selected(request('gender') === 'other')>Other</option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Age Range</label>
                <div class="d-flex gap-2 align-items-center">
                  <input type="number" name="age_min" value="{{ request('age_min') }}" class="form-control"
                    placeholder="Min" min="0" max="120" />
                  <span>to</span>
                  <input type="number" name="age_max" value="{{ request('age_max') }}" class="form-control"
                    placeholder="Max" min="0" max="120" />
                </div>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Organisation</label>
                <input type="text" name="organisation" value="{{ request('organisation') }}" class="form-control"
                  placeholder="Organisation name" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Province</label>
                <select class="form-select" name="province">
                  <option value="">All</option>
                  <option value="harare" @selected(request('province') === 'harare')>Harare</option>
                  <option value="bulawayo" @selected(request('province') === 'bulawayo')>Bulawayo</option>
                  <option value="manicaland" @selected(request('province') === 'manicaland')>Manicaland</option>
                  <option value="mashonaland_central" @selected(request('province') === 'mashonaland_central')>Mashonaland
                    Central</option>
                  <option value="mashonaland_east" @selected(request('province') === 'mashonaland_east')>Mashonaland East
                  </option>
                  <option value="mashonaland_west" @selected(request('province') === 'mashonaland_west')>Mashonaland West
                  </option>
                  <option value="masvingo" @selected(request('province') === 'masvingo')>Masvingo</option>
                  <option value="matabeleland_north" @selected(request('province') === 'matabeleland_north')>Matabeleland
                    North</option>
                  <option value="matabeleland_south" @selected(request('province') === 'matabeleland_south')>Matabeleland
                    South</option>
                  <option value="midlands" @selected(request('province') === 'midlands')>Midlands</option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Collection Region</label>
                <select class="form-select" name="collection_region">
                  <option value="">All</option>
                  <option value="harare" @selected(request('collection_region') === 'harare')>Harare</option>
                  <option value="bulawayo" @selected(request('collection_region') === 'bulawayo')>Bulawayo</option>
                  <option value="mutare" @selected(request('collection_region') === 'mutare')>Mutare</option>
                  <option value="masvingo" @selected(request('collection_region') === 'masvingo')>Masvingo</option>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Application Category</label>
                <select class="form-select" name="category">
                  <option value="">All categories</option>
                  @foreach(\App\Models\Application::accreditationCategories() as $code => $label)
                    <option value="{{ $code }}" @selected(request('category') === $code)>{{ $code }} — {{ is_array($label) ? ($label['name'] ?? $code) : $label }}</option>
                  @endforeach
                  @foreach(\App\Models\Application::massMediaCategories() as $code => $label)
                    <option value="{{ $code }}" @selected(request('category') === $code)>{{ $code }} — {{ is_array($label) ? ($label['name'] ?? $code) : $label }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Nationality</label>
                <input type="text" name="nationality" value="{{ request('nationality') }}" class="form-control"
                  placeholder="Nationality" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Date From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" />
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label small fw-bold">Date To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" />
              </div>

            </div>
          </div>
          <div class="modal-footer">
            <input type="hidden" name="year" value="{{ request('year', now()->year) }}" />
            <input type="hidden" name="processing_status" value="{{ request('processing_status', 'all') }}" />
            <input type="hidden" name="application_type" value="{{ request('application_type') }}" />
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark"><i class="ri-filter-3-line me-1"></i>Apply Filters</button>
          </div>
        </form>
      </div>
    </div>
  </div>

@endsection
