@extends('layouts.portal')

@section('title', 'Conversation')

@section('content')
@php
  $ref = $application->reference ?? ('APP-' . $application->id);
  $me = auth()->user();
@endphp

<div class="zmc-dashboard-wrapper" style="font-family:'Roboto',sans-serif;color:#334155;">

  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
      <h4 class="fw-bold m-0" style="font-size:22px;color:#1e293b;">Conversation</h4>
      <div class="text-muted mt-1" style="font-size:13px;">
        <i class="ri-hashtag me-1"></i> Ref: <span class="badge bg-light text-dark border">{{ $ref }}</span>
        <span class="mx-2">•</span>
        Status: <span class="fw-bold" style="color:var(--zmc-accent-dark)">{{ ucwords(str_replace('_',' ', $application->status ?? '—')) }}</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('messages.index') }}" class="btn btn-white border shadow-sm btn-sm px-3" title="Back">
        <i class="ri-arrow-left-line me-1"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-start gap-2">
      <i class="ri-checkbox-circle-line" style="font-size:18px;line-height:1;"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <div class="zmc-card p-0 shadow-sm border-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <h6 class="fw-bold m-0"><i class="ri-chat-3-line me-2" style="color:var(--zmc-accent)"></i>Thread</h6>
      <span class="small text-muted">{{ $messages->count() }} messages</span>
    </div>

    <div class="p-3" style="background:#fff7ed;border-bottom:1px solid rgba(15,23,42,.08);">
      <div class="zmc-chat-thread" style="min-height:320px;background:rgba(255,255,255,.65);border:1px dashed rgba(15,23,42,.15);padding:12px;">
        @forelse($messages as $m)
          @php
            $mine = $me && (int)$m->from_user_id === (int)$me->id;
            $when = $m->sent_at ? $m->sent_at->format('d M Y H:i') : ($m->created_at?->format('d M Y H:i') ?? '');
            $who = $mine ? 'You' : (\App\Models\User::find($m->from_user_id)?->name ?? 'User');
          @endphp
          <div class="mb-2 d-flex {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
            <div style="max-width:780px;" class="p-2 px-3 border rounded-3 bg-white">
              <div class="d-flex justify-content-between gap-3">
                <div class="fw-bold" style="font-size:12px;">{{ $who }}</div>
                <div class="text-muted" style="font-size:11px;white-space:nowrap;">{{ $when }}</div>
              </div>
              <div style="font-size:13px;white-space:pre-wrap;">{{ $m->body }}</div>
            </div>
          </div>
        @empty
          <div class="text-muted small">No messages yet.</div>
        @endforelse
      </div>
    </div>

    <div class="p-3">
      <form method="POST" action="{{ route('messages.send', $application->id) }}" class="d-flex gap-2 align-items-end">
        @csrf
        <div class="flex-grow-1">
          <label class="form-label zmc-lbl mb-2">Message <span class="text-danger">*</span></label>
          <textarea name="body" class="form-control zmc-chat-input" rows="2" required placeholder="Type your message..."></textarea>
        </div>
        <button type="submit" class="btn btn-dark zmc-send-btn">
          <i class="fa-solid fa-paper-plane me-1"></i>Send
        </button>
      </form>
      <div class="form-text mt-2">All messages are shared inside the portal for this application.</div>
    </div>
  </div>

</div>
@endsection
