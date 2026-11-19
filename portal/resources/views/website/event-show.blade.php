<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $event->title }} — Zimbabwe Media Commission</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #334155; }
    .zmc-header { background: #1e293b; color: #fff; padding: 16px 0; }
    .zmc-header img { height: 48px; }
    .event-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .event-hero { background: linear-gradient(135deg, #92400e 0%, #b45309 100%); color: #fff; padding: 40px 36px; }
    .event-hero h1 { font-size: 24px; font-weight: 800; margin: 0 0 12px; }
    .event-detail { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 6px; opacity: 0.9; }
    .event-body { padding: 36px; font-size: 15px; line-height: 1.8; white-space: pre-wrap; }
    .event-image { width: 100%; max-height: 400px; object-fit: cover; }
  </style>
  @include('layouts.favicon')
</head>
<body>
  <div class="zmc-header">
    <div class="container d-flex align-items-center gap-3">
      <img src="{{ asset('zmc_logo_fresh_nobg.png') }}" alt="ZMC Logo">
      <div>
        <div class="fw-bold" style="font-size:16px;">ZIMBABWE MEDIA COMMISSION</div>
        <div style="font-size:11px; opacity:0.7;">Event</div>
      </div>
    </div>
  </div>

  <div class="container py-4" style="max-width:800px;">
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm mb-3">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>

    <div class="event-card">
      @if($event->image_path)
        <img src="{{ asset('storage/' . $event->image_path) }}" alt="{{ $event->title }}" class="event-image">
      @endif
      <div class="event-hero">
        <h1>{{ $event->title }}</h1>
        @if($event->starts_at)
          <div class="event-detail">
            <i class="ri-calendar-line"></i>
            {{ $event->starts_at->format('d F Y, H:i') }}
            @if($event->ends_at) &ndash; {{ $event->ends_at->format('d F Y, H:i') }} @endif
          </div>
        @endif
        @if($event->location)
          <div class="event-detail">
            <i class="ri-map-pin-line"></i>
            {{ $event->location }}
          </div>
        @endif
      </div>
      @if($event->description)
        <div class="event-body">{{ strip_tags((string) $event->description) }}</div>
      @endif

      @if($event->attachment_path)
        <div class="px-4 pb-4">
          <a href="{{ asset('storage/' . $event->attachment_path) }}" target="_blank" class="btn btn-outline-dark btn-sm">
            <i class="ri-download-2-line me-1"></i> Download Attachment ({{ $event->attachment_original_name ?? 'File' }})
          </a>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
