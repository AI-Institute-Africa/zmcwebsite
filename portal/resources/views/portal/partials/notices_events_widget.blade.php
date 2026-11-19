@php
  $portalKey = $portalKey ?? 'both';
  $latestNotices = \App\Models\Notice::where('is_published',true)->whereIn('target_portal',[$portalKey,'both'])->latest('published_at')->take(3)->get();
  $latestEvents = \App\Models\Event::where('is_published',true)->whereIn('target_portal',[$portalKey,'both'])->orderByRaw('starts_at is null, starts_at asc')->take(3)->get();
@endphp

<div class="row g-3 mb-4">
  <div class="col-12 col-lg-6">
    <div class="zmc-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="ri-megaphone-line me-2" style="color:var(--zmc-accent)"></i>Notices</div>
        <a class="small fw-bold text-decoration-none" href="{{ route('portal.notices_events', ['portal'=>$portalKey]) }}">View all</a>
      </div>
      @forelse($latestNotices as $n)
        <div class="mb-2">
          <div class="fw-bold" style="font-size:13px;">{{ $n->title }}</div>
          <div class="text-muted" style="font-size:12px;">{{ optional($n->published_at ?? $n->created_at)->format('d M Y H:i') }}</div>
        </div>
      @empty
        <div class="text-muted small">No notices.</div>
      @endforelse
    </div>
  </div>

  <div class="col-12 col-lg-6">
    <div class="zmc-card h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-bold"><i class="ri-calendar-event-line me-2" style="color:var(--zmc-accent)"></i>Events</div>
        <a class="small fw-bold text-decoration-none" href="{{ route('portal.notices_events', ['portal'=>$portalKey]) }}">View all</a>
      </div>
      @forelse($latestEvents as $e)
        <div class="mb-2">
          <div class="fw-bold" style="font-size:13px;">{{ $e->title }}</div>
          <div class="text-muted" style="font-size:12px;">{{ $e->starts_at ? $e->starts_at->format('d M Y H:i') : 'Date TBA' }}{{ $e->location ? ' • '.$e->location : '' }}</div>
        </div>
      @empty
        <div class="text-muted small">No events.</div>
      @endforelse
    </div>
  </div>
</div>
