@extends('layouts.portal')

@section('title', 'Website Message')

@section('content')
@php
  $ref = $message->reference_number ?: 'ENQ-' . str_pad($message->id, 6, '0', STR_PAD_LEFT);
  $statusLabels = ['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'];
@endphp
<div class="container-fluid py-3">
  <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4"><div><a href="{{ route('staff.pr.messages.index') }}" class="text-muted small"><i class="ri-arrow-left-line"></i> Website messages</a><h1 class="h4 mt-2 mb-1">{{ $message->subject ?: 'Website enquiry' }}</h1><div class="text-muted small">{{ $ref }} · received {{ $message->created_at?->format('d M Y H:i') }} ({{ $ageHours }} hours ago)</div></div><span class="badge bg-{{ $message->status === 'open' ? 'warning text-dark' : ($message->status === 'in_progress' ? 'primary' : ($message->status === 'resolved' ? 'success' : 'secondary')) }} align-self-center">{{ $statusLabels[$message->status] ?? $message->status }}</span></div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <div class="row g-3">
    <div class="col-xl-8">
      <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between gap-3 mb-3"><div><div class="small text-muted">From</div><div class="fw-semibold">{{ $message->full_name }}</div><a href="mailto:{{ $message->email }}">{{ $message->email }}</a>@if($message->phone)<div class="small text-muted">{{ $message->phone }}</div>@endif</div><div class="text-end small text-muted">{{ $message->subject ?: 'General enquiry' }}</div></div>
        <div class="border rounded p-3" style="white-space:pre-wrap">{{ $message->message }}</div>
        @if($message->attachment_path)<div class="mt-3"><a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ Storage::url($message->attachment_path) }}"><i class="ri-attachment-2 me-1"></i>{{ $message->attachment_original_name ?: 'Open attachment' }}</a></div>@endif
      </div></div>
      <div class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">Reply history</h2>@forelse($message->communications as $reply)<div class="border-top py-3"><div class="d-flex justify-content-between"><strong>{{ $reply->subject }}</strong><span class="small text-muted">{{ $reply->sent_at?->format('d M Y H:i') }}</span></div><div class="small text-muted">{{ $reply->sender?->name ?: 'System' }} · {{ $reply->delivery_status }}</div><div class="mt-2" style="white-space:pre-wrap">{{ $reply->body }}</div></div>@empty<div class="text-muted small">No replies have been sent.</div>@endforelse</div></div>
      <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h6">Send reply</h2><form method="POST" action="{{ route('staff.pr.messages.reply', $message) }}">@csrf<div class="mb-2"><label class="form-label small">Subject</label><input name="subject" required maxlength="255" class="form-control" value="{{ old('subject', 'Re: '.($message->subject ?: 'Your enquiry to ZMC')) }}"></div><div class="mb-2"><label class="form-label small">Message</label><textarea name="body" required rows="6" maxlength="10000" class="form-control" placeholder="Write a clear response to the sender..."></textarea></div><button class="btn btn-dark"><i class="ri-send-plane-line me-1"></i>Send and log reply</button></form></div></div>
    </div>
    <div class="col-xl-4">
      <div class="card border-0 shadow-sm mb-3"><div class="card-body"><h2 class="h6">Workflow</h2><form method="POST" action="{{ route('staff.pr.messages.update', $message) }}">@csrf @method('PATCH')<label class="form-label small">Status</label><select name="status" class="form-select mb-2">@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected($message->status===$key)>{{ $label }}</option>@endforeach</select><label class="form-label small">Priority</label><select name="priority" class="form-select mb-2">@foreach(['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent'] as $key=>$label)<option value="{{ $key }}" @selected(($message->priority ?: 'normal')===$key)>{{ $label }}</option>@endforeach</select><label class="form-label small">Assigned to</label><select name="assigned_to" class="form-select mb-2"><option value="">Unassigned</option>@foreach($officers as $officer)<option value="{{ $officer->id }}" @selected($message->handled_by===$officer->id)>{{ $officer->name }}</option>@endforeach</select><label class="form-label small">Response due</label><input type="datetime-local" name="response_due_at" class="form-control mb-3" value="{{ old('response_due_at', $message->response_due_at?->format('Y-m-d\TH:i')) }}"><button class="btn btn-primary w-100">Save workflow</button></form></div></div>
      <div class="card border-0 shadow-sm"><div class="card-body"><h2 class="h6">Private notes</h2><p class="small text-muted">Only staff can see these notes.</p><form method="POST" action="{{ route('staff.pr.messages.notes', $message) }}">@csrf @method('PATCH')<textarea name="internal_notes" rows="7" maxlength="10000" class="form-control mb-2" placeholder="Add handover or follow-up notes...">{{ old('internal_notes', $message->internal_notes) }}</textarea><button class="btn btn-outline-dark w-100">Save private notes</button></form></div></div>
    </div>
  </div>
</div>
@endsection
