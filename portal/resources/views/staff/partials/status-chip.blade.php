{{-- Shared status chip. Usage: @include('staff.partials.status-chip', ['status' => $application->status]) --}}
@php
  $chipStatus = $status ?? null;
  $chipTone = \App\Support\ApplicationStatus::tone($chipStatus);
  $chipDetail = \App\Support\ApplicationStatus::detail($chipStatus);
@endphp
<span class="zmc-chip zmc-chip--{{ $chipTone }}" title="{{ $chipDetail }}">
  <i class="{{ \App\Support\ApplicationStatus::icon($chipStatus) }}" aria-hidden="true"></i>
  {{ \App\Support\ApplicationStatus::label($chipStatus) }}
</span>
