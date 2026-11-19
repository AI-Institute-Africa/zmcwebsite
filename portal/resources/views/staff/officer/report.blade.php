@extends('layouts.portal')
@section('title', $title ?? 'Accreditation Officer')

@section('content')
  <div class="zmc-dashboard-wrapper" style="font-family: var(--font-primary); color: var(--zmc-text-dark);">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
      <div>
        <h4 class="fw-bold m-0" style="font-size: var(--font-size-2xl); color:#1e293b;">{{ $title ?? 'Page' }}</h4>
        @if(!empty($subtitle))
          <div class="text-muted mt-1" style="font-size: var(--font-size-base);">
            <i class="ri-information-line me-1"></i>{{ $subtitle }}
          </div>
        @endif
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @foreach(($headerActions ?? []) as $action)
          <a href="{{ $action['url'] }}" class="btn btn-white border shadow-sm btn-sm px-3">
            <i class="{{ $action['icon'] ?? 'ri-arrow-right-line' }} me-1"></i>{{ $action['label'] }}
          </a>
        @endforeach
        <a href="{{ url()->current() }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Refresh">
          <i class="ri-refresh-line me-1"></i> Refresh
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    @if(!empty($kpis))
      <div class="row g-3 mb-4">
        @foreach($kpis as $kpi)
          <div class="col-6 col-md-4 col-xl-3">
            <div class="card shadow-sm h-100 border-0">
              <div class="card-body py-3">
                <div class="text-muted text-uppercase" style="font-size: var(--font-size-xs); letter-spacing:.07em;">
                  {{ $kpi['label'] }}
                </div>
                <div class="fw-bold mt-1 text-{{ $kpi['color'] ?? 'dark' }}" style="font-size: var(--font-size-2xl);">
                  {{ $kpi['value'] }}
                </div>
                @if(!empty($kpi['hint']))
                  <div class="text-muted" style="font-size: var(--font-size-xs);">{{ $kpi['hint'] }}</div>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    @if(!empty($filters))
      <div class="card shadow-sm mb-4 border-0">
        <div class="card-body py-3">
          <form method="GET" action="{{ url()->current() }}" class="row g-2 align-items-end">
            @foreach($filters as $filter)
              <div class="col-12 col-md-4 col-xl-3">
                <label class="form-label small fw-bold mb-1">{{ $filter['label'] }}</label>
                @if(($filter['type'] ?? 'text') === 'select')
                  <select name="{{ $filter['name'] }}" class="form-select form-select-sm">
                    @foreach(($filter['options'] ?? []) as $optionValue => $optionLabel)
                      <option value="{{ $optionValue }}" {{ (string) ($filter['value'] ?? '') === (string) $optionValue ? 'selected' : '' }}>
                        {{ $optionLabel }}
                      </option>
                    @endforeach
                  </select>
                @elseif(($filter['type'] ?? 'text') === 'date')
                  <input type="date" name="{{ $filter['name'] }}" value="{{ $filter['value'] ?? '' }}" class="form-control form-control-sm">
                @else
                  <input type="text" name="{{ $filter['name'] }}" value="{{ $filter['value'] ?? '' }}"
                    placeholder="{{ $filter['placeholder'] ?? '' }}" class="form-control form-control-sm">
                @endif
              </div>
            @endforeach
            <div class="col-12 col-md-auto d-flex gap-2">
              <button type="submit" class="btn btn-dark btn-sm px-3"><i class="ri-filter-3-line me-1"></i>Apply</button>
              <a href="{{ url()->current() }}" class="btn btn-white border btn-sm px-3">Reset</a>
            </div>
          </form>
        </div>
      </div>
    @endif

    @if(!empty($bars) && !empty($bars['labels']))
      @php
        $barMax = 1;
        foreach (($bars['series'] ?? []) as $series) {
          foreach (($series['values'] ?? []) as $value) {
            $barMax = max($barMax, (int) $value);
          }
        }
      @endphp
      <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="fw-bold m-0">{{ $bars['title'] ?? 'Overview' }}</h6>
            <div class="d-flex gap-3">
              @foreach(($bars['series'] ?? []) as $series)
                <span class="d-inline-flex align-items-center gap-1" style="font-size: var(--font-size-xs);">
                  <span style="width:10px;height:10px;border-radius:2px;display:inline-block;background:{{ $series['color'] ?? '#1a1a1a' }}"></span>
                  {{ $series['label'] ?? '' }}
                </span>
              @endforeach
            </div>
          </div>
          <div class="d-flex flex-column gap-2">
            @foreach($bars['labels'] as $index => $label)
              <div class="d-flex align-items-center gap-2">
                <div class="text-muted text-nowrap" style="width:78px;font-size:var(--font-size-xs);">{{ $label }}</div>
                <div class="flex-grow-1 d-flex flex-column gap-1">
                  @foreach(($bars['series'] ?? []) as $series)
                    @php $value = (int) ($series['values'][$index] ?? 0); @endphp
                    <div class="d-flex align-items-center gap-2">
                      <div style="height:7px;border-radius:4px;background:{{ $series['color'] ?? '#1a1a1a' }};width:{{ max(1, round(($value / $barMax) * 100, 1)) }}%;"></div>
                      <span class="text-muted" style="font-size:var(--font-size-xs);">{{ $value }}</span>
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    @foreach(($sections ?? []) as $section)
      <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
          <h6 class="fw-bold mb-3">{{ $section['title'] ?? 'Records' }}</h6>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  @foreach(($section['columns'] ?? []) as $column)
                    <th class="text-nowrap">{{ $column }}</th>
                  @endforeach
                </tr>
              </thead>
              <tbody>
                @forelse(($section['rows'] ?? []) as $row)
                  <tr>
                    @foreach($row as $cell)
                      <td>{!! $cell !!}</td>
                    @endforeach
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ count($section['columns'] ?? []) }}" class="text-center text-muted py-4">
                      {{ $section['empty'] ?? 'No records found.' }}
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
          @if(!empty($section['paginator']) && method_exists($section['paginator'], 'links'))
            <div class="mt-3">{{ $section['paginator']->links() }}</div>
          @endif
        </div>
      </div>
    @endforeach
  </div>
@endsection
