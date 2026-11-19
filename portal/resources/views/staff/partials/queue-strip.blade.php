{{-- "My queue" strip. Usage: @include('staff.partials.queue-strip', ['role' => 'registrar']) --}}
@php
  $qsRole = $role ?? session('active_staff_role');
  $qs = \App\Support\StaffQueue::summary(auth()->user(), $qsRole);
@endphp
@if($qs)
  @php
    $qsAge = $qs['oldest_hours'];
    $qsAgeText = $qsAge === null ? '—' : ($qsAge >= 48 ? floor($qsAge / 24) . ' days' : $qsAge . ' h');
  @endphp
  <section class="zmc-queue" aria-label="My queue">
    <div class="zmc-queue__intro">
      <div class="zmc-queue__title"><i class="ri-inbox-line" aria-hidden="true"></i> My queue</div>
      <div class="zmc-queue__sub">{{ $qs['label'] }}</div>
    </div>
    <a class="zmc-queue__stat" @if($qs['url']) href="{{ $qs['url'] }}" @endif>
      <span class="zmc-queue__num">{{ $qs['waiting'] }}</span><span class="zmc-queue__lbl">Waiting on you</span>
    </a>
    <a class="zmc-queue__stat {{ $qs['overdue'] > 0 ? 'is-alert' : '' }}" @if($qs['url']) href="{{ $qs['url'] }}" @endif>
      <span class="zmc-queue__num">{{ $qs['overdue'] }}</span><span class="zmc-queue__lbl">Overdue (past SLA)</span>
    </a>
    <div class="zmc-queue__stat">
      <span class="zmc-queue__num">{{ $qs['today'] }}</span><span class="zmc-queue__lbl">Updated in last 24 h</span>
    </div>
    <div class="zmc-queue__stat">
      <span class="zmc-queue__num">{{ $qsAgeText }}</span><span class="zmc-queue__lbl">Oldest item waiting</span>
    </div>
  </section>
@endif
