@extends('layouts.portal')
@section('title','Notices & Events')
@section('page_title', 'Notices & Events')
@section('content')
<div id="notices-page" style="color:#334155;">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px; color:#1e293b;">Notices & Events</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-information-line me-1"></i>
        Stay updated with the latest announcements from ZMC.
      </div>
    </div>
  </div>

  @php
    $isMedia = str_contains(url()->previous(), 'media-house') || request()->routeIs('mediahouse.*') || session('portal_context') === 'mediahouse';
  @endphp

  <div class="row g-3">
    @unless($isMedia)
    <div class="col-12 col-lg-3">
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-menu-line me-2" style="color:var(--zmc-accent)"></i>Navigation</h6>
        <div class="list-group list-group-flush">
          <a href="{{ route('accreditation.home') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-dashboard-line me-2"></i> Dashboard
          </a>
          <a href="{{ route('accreditation.new') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-file-add-line me-2"></i> New Application
          </a>
          <a href="{{ route('accreditation.renewal') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-refresh-line me-2"></i> Renewal
          </a>
          <a href="{{ route('accreditation.replacement') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-exchange-line me-2"></i> Replacement
          </a>
          <a href="{{ url('/portal/notices-events') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small active fw-bold" style="background:transparent; color:var(--zmc-accent);">
            <i class="ri-megaphone-line me-2"></i> Notices & Events
          </a>
          <a href="{{ route('accreditation.requirements') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-file-list-3-line me-2"></i> Requirements
          </a>
          <a href="{{ route('accreditation.howto') }}" class="list-group-item list-group-item-action border-0 px-0 py-2 small">
            <i class="ri-question-line me-2"></i> How It Works
          </a>
        </div>
      </div>
    </div>
    @endunless

    <div class="col-12 {{ $isMedia ? 'col-lg-7' : 'col-lg-5' }}">
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-megaphone-line me-2" style="color:var(--zmc-accent)"></i>Notices</h6>
        @forelse($notices as $n)
          <div class="border-bottom pb-3 mb-3">
            @if($n->image_path)
              <div class="mb-2">
                <img src="{{ asset('storage/' . $n->image_path) }}" alt="{{ $n->title }}" class="rounded" style="max-width:100%;max-height:180px;object-fit:cover;">
              </div>
            @endif
            <div class="fw-bold text-dark" style="text-transform: none;">{{ $n->title }}</div>
            <div class="text-muted small">{{ optional($n->published_at)->format('d M Y H:i') }}</div>
            <div class="mt-2" style="white-space:pre-wrap; font-size:13px;">{{ Str::limit($n->body, 200) }}</div>
            <a href="https://21f81a24-9af7-4336-aff0-069d8d434f4f-00-2rd0z14q2x1f8.kirk.replit.dev/notices/{{ $n->id }}" target="_blank" class="btn btn-sm btn-outline-dark mt-2">
              <i class="ri-external-link-line me-1"></i> Read More
            </a>
          </div>
        @empty
          <div class="text-muted">No notices at the moment.</div>
        @endforelse
      </div>
    </div>

    <div class="col-12 {{ $isMedia ? 'col-lg-5' : 'col-lg-4' }}">
      <div class="zmc-card">
        <h6 class="fw-bold mb-3"><i class="ri-calendar-event-line me-2" style="color:var(--zmc-accent)"></i>Events</h6>
        @forelse($events as $e)
          <div class="border-bottom pb-3 mb-3">
            @if($e->image_path)
              <div class="mb-2">
                <img src="{{ asset('storage/' . $e->image_path) }}" alt="{{ $e->title }}" class="rounded" style="max-width:100%;max-height:180px;object-fit:cover;">
              </div>
            @endif
            <div class="fw-bold text-dark" style="text-transform: none;">{{ $e->title }}</div>
            <div class="text-muted small">
              @if($e->starts_at)
                {{ $e->starts_at->format('d M Y, H:i') }}
                @if($e->ends_at) &ndash; {{ $e->ends_at->format('d M Y, H:i') }} @endif
              @endif
              @if($e->location) - {{ $e->location }} @endif
            </div>
            @if($e->description)
              <div class="mt-2" style="white-space:pre-wrap; font-size:13px;">{{ Str::limit($e->description, 150) }}</div>
            @endif
            <a href="https://21f81a24-9af7-4336-aff0-069d8d434f4f-00-2rd0z14q2x1f8.kirk.replit.dev/events/{{ $e->id }}" target="_blank" class="btn btn-sm btn-outline-dark mt-2">
              <i class="ri-external-link-line me-1"></i> Read More
            </a>
          </div>
        @empty
          <div class="text-muted">No upcoming events.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
