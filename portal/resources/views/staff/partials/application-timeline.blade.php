{{-- Application timeline. Usage: @include('staff.partials.application-timeline', ['application' => $application]) --}}
@php
  $tlEvents = collect();
  try {
    foreach (\App\Models\ApplicationComment::where('application_id', $application->id)->latest()->limit(40)->get() as $c) {
      $tlEvents->push([
        'at' => $c->created_at,
        'icon' => 'ri-chat-3-line',
        'title' => (\App\Models\ApplicationComment::commentTypes()[$c->comment_type] ?? 'Note'),
        'by' => ucwords(str_replace('_', ' ', (string) $c->user_role)),
        'text' => $c->body,
        'status' => $c->application_status_at_time,
        'internal' => $c->visibility === \App\Models\ApplicationComment::VISIBILITY_INTERNAL,
      ]);
    }
    foreach (\App\Models\AuditTrail::where('entity_type', 'Application')->where('entity_id', $application->id)->orderByDesc('created_at')->limit(40)->get() as $a) {
      $tlEvents->push([
        'at' => $a->created_at,
        'icon' => 'ri-history-line',
        'title' => ucwords(str_replace('_', ' ', (string) $a->action)),
        'by' => trim(($a->user_name ?: 'System') . ($a->user_role ? ' · ' . ucwords(str_replace('_', ' ', $a->user_role)) : '')),
        'text' => $a->description,
        'status' => null,
        'internal' => false,
      ]);
    }
  } catch (\Throwable $e) {
    $tlEvents = collect();
  }
  $tlEvents = $tlEvents->filter(fn ($e) => $e['at'])->sortByDesc('at')->take(40)->values();
@endphp
<section class="zmc-timeline card" aria-label="Application timeline">
  <div class="card-header fw-bold"><i class="ri-time-line me-1" aria-hidden="true"></i> Timeline</div>
  @if($tlEvents->isEmpty())
    <div class="p-3 text-muted">No activity recorded yet.</div>
  @else
    <ol class="zmc-timeline__list">
      @foreach($tlEvents as $e)
        <li class="zmc-timeline__item">
          <span class="zmc-timeline__dot"><i class="{{ $e['icon'] }}" aria-hidden="true"></i></span>
          <div class="zmc-timeline__body">
            <div class="zmc-timeline__head">
              <strong>{{ $e['title'] }}</strong>
              @if($e['status']) @include('staff.partials.status-chip', ['status' => $e['status']]) @endif
              @if($e['internal'])<span class="zmc-chip zmc-chip--secondary">Internal</span>@endif
            </div>
            @if(!empty($e['text']))<div class="zmc-timeline__text">{{ \Illuminate\Support\Str::limit(strip_tags((string) $e['text']), 280) }}</div>@endif
            <div class="zmc-timeline__meta">{{ $e['by'] }} · <time datetime="{{ $e['at']->toIso8601String() }}" title="{{ $e['at']->format('d M Y H:i') }}">{{ $e['at']->diffForHumans() }}</time></div>
          </div>
        </li>
      @endforeach
    </ol>
  @endif
</section>
