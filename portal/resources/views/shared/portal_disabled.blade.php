@extends('layouts.portal')

@section('title', 'Unavailable')

@section('content')
<div class="container py-5">
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <h4 class="fw-bold mb-2">{{ $portal ?? 'Portal' }} is currently unavailable</h4>
      <p class="text-muted mb-0">Access to this portal has been temporarily disabled by the system administrator.</p>
    </div>
  </div>
</div>
@endsection
