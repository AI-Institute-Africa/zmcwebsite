<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $notice->title }} — Zimbabwe Media Commission</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
  <style>
    body { font-family: 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #334155; }
    .zmc-header { background: #1e293b; color: #fff; padding: 16px 0; }
    .zmc-header img { height: 48px; }
    .notice-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; }
    .notice-hero { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: #fff; padding: 40px 36px; }
    .notice-hero h1 { font-size: 24px; font-weight: 800; margin: 0 0 8px; }
    .notice-meta { font-size: 13px; opacity: 0.8; }
    .notice-body { padding: 36px; font-size: 15px; line-height: 1.8; white-space: pre-wrap; }
    .notice-image { width: 100%; max-height: 400px; object-fit: cover; }
  </style>
  @include('layouts.favicon')
</head>
<body>
  <div class="zmc-header">
    <div class="container d-flex align-items-center gap-3">
      <img src="{{ asset('zmc_logo_fresh_nobg.png') }}" alt="ZMC Logo">
      <div>
        <div class="fw-bold" style="font-size:16px;">ZIMBABWE MEDIA COMMISSION</div>
        <div style="font-size:11px; opacity:0.7;">Notice</div>
      </div>
    </div>
  </div>

  <div class="container py-4" style="max-width:800px;">
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm mb-3">
      <i class="ri-arrow-left-line me-1"></i> Back
    </a>

    <div class="notice-card">
      @if($notice->image_path)
        <img src="{{ asset('storage/' . $notice->image_path) }}" alt="{{ $notice->title }}" class="notice-image">
      @endif
      <div class="notice-hero">
        <h1>{{ $notice->title }}</h1>
        <div class="notice-meta">
          <i class="ri-calendar-line me-1"></i>
          Published {{ optional($notice->published_at)->format('d F Y') }}
        </div>
      </div>
      <div class="notice-body">{{ strip_tags((string) $notice->body) }}</div>

      @if($notice->attachment_path)
        <div class="px-4 pb-4">
          <a href="{{ asset('storage/' . $notice->attachment_path) }}" target="_blank" class="btn btn-outline-dark btn-sm">
            <i class="ri-download-2-line me-1"></i> Download Attachment ({{ $notice->attachment_original_name ?? 'File' }})
          </a>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
