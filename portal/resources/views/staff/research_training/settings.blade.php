@extends('layouts.portal')
@section('title','Complaints Settings')
@section('content')
<div class="container-fluid py-4" style="font-family:Inter,sans-serif;background:#f8fafc;min-height:100vh;">
  <div class="mb-4"><h1 class="fw-bold mb-1">Settings</h1><p class="text-muted mb-0">Configure acknowledgement and response workflow for complaints.</p></div>
  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  <div class="card border-0 shadow-sm rounded-4" style="max-width:720px;"><div class="card-body"><form method="POST" action="{{ route('staff.research.settings.save') }}">@csrf
    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" name="acknowledgement_enabled" value="1" id="ack" @checked($settings['acknowledgement_enabled'] ?? true)><label class="form-check-label fw-semibold" for="ack">Send acknowledgement email automatically</label><div class="small text-muted mt-1">Uses the approved Complaints Desk acknowledgement and includes the dated reference number.</div></div>
    <div class="mb-4"><label class="form-label fw-semibold">Default response period</label><div class="input-group" style="max-width:260px;"><input type="number" min="1" max="90" name="default_response_days" value="{{ $settings['default_response_days'] ?? 10 }}" class="form-control"><span class="input-group-text">days</span></div></div>
    <button class="btn btn-dark"><i class="ri-save-line me-1"></i>Save settings</button>
  </form></div></div>
</div>
@endsection
