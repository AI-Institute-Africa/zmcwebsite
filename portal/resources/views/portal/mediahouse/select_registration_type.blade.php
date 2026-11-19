@extends('layouts.portal')

@section('title', 'New Media House Registration')

@section('content')
<style>
  .registration-choice{--ink:#0B0F17;--line:#E9EDF3;--gold:#D4A017;--muted:#6B7A90;font-family:'Roboto',system-ui,-apple-system,'Segoe UI',sans-serif;color:#334155;max-width:1080px;margin:0 auto}
  .registration-hero{position:relative;overflow:hidden;border-radius:26px;padding:32px;color:#fff;background:radial-gradient(120% 180% at 100% 0%,#26313F 0%,#0B0F17 58%),#0B0F17;box-shadow:0 30px 70px -38px rgba(11,15,23,.85)}
  .registration-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(52% 90% at 88% 6%,rgba(212,160,23,.42),transparent 62%),radial-gradient(45% 85% at 4% 105%,rgba(56,189,248,.14),transparent 68%)}
  .registration-hero>*{position:relative;z-index:1}.registration-hero h1{font-size:clamp(24px,3vw,34px);font-weight:900;letter-spacing:-.03em;margin:14px 0 7px}.registration-hero p{color:rgba(255,255,255,.7);max-width:60ch;margin:0;line-height:1.6;font-size:14px}
  .registration-eyebrow{display:inline-flex;gap:8px;align-items:center;padding:6px 12px;border-radius:999px;background:rgba(212,160,23,.14);border:1px solid rgba(212,160,23,.32);color:#F6DC93;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.registration-back{color:rgba(255,255,255,.78);font-size:12px;text-decoration:none}
  .registration-card{height:100%;background:#fff;border:1px solid var(--line);border-radius:20px;padding:24px;box-shadow:0 1px 2px rgba(16,24,40,.04),0 20px 46px -36px rgba(16,24,40,.45);transition:transform .18s,box-shadow .18s,border-color .18s}.registration-card:hover{transform:translateY(-3px);border-color:#D4A017;box-shadow:0 18px 40px -28px rgba(11,15,23,.42)}
  .registration-icon{width:54px;height:54px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:#FDF7E7;color:#9A7208;font-size:25px}.registration-card h2{font-size:16px;font-weight:850;color:var(--ink);margin:18px 0 7px}.registration-card p{font-size:13px;line-height:1.65;color:var(--muted);min-height:64px;margin-bottom:18px}.registration-action{display:inline-flex;align-items:center;gap:7px;color:#0B0F17;font-size:12px;font-weight:800;text-decoration:none}.registration-action i{color:var(--gold);font-size:17px}
</style>

<div class="registration-choice">
  <div class="registration-hero mb-4">
    <a href="{{ route('mediahouse.portal') }}" class="registration-back"><i class="ri-arrow-left-line me-1"></i> Back to Dashboard</a>
    <div class="registration-eyebrow mt-4"><i class="ri-building-2-line"></i> Media House Registration</div>
    <h1>Start a new registration</h1>
    <p>Choose the application type that matches your media organisation. Your progress and uploaded documents can be saved as a draft and continued later.</p>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3"><div><h2 class="h5 fw-bold mb-1" style="color:#0B0F17;">Choose registration type</h2><div class="small text-muted">Select one application to continue.</div></div><span class="small text-muted"><i class="ri-shield-check-line me-1" style="color:#D4A017"></i>Secure application process</span></div>

  <div class="row g-3">
    <div class="col-12 col-md-6"><a href="{{ route('mediahouse.ap1.create') }}" class="text-decoration-none"><div class="registration-card"><div class="registration-icon"><i class="ri-building-2-line"></i></div><h2>AP1 — Mass Media Service</h2><p>For Zimbabwe-based newspapers, magazines, radio and television stations, online publications, and other mass media services.</p><span class="registration-action">Start AP1 application <i class="ri-arrow-right-line"></i></span></div></a></div>
    <div class="col-12 col-md-6"><a href="{{ route('mediahouse.ap2.create') }}" class="text-decoration-none"><div class="registration-card"><div class="registration-icon"><i class="ri-newspaper-line"></i></div><h2>AP2 — News Agency</h2><p>For organisations that gather, write, distribute, or syndicate news and current-affairs content to media outlets.</p><span class="registration-action">Start AP2 application <i class="ri-arrow-right-line"></i></span></div></a></div>
    <div class="col-12 col-md-6"><a href="{{ route('mediahouse.ap4.create') }}" class="text-decoration-none"><div class="registration-card"><div class="registration-icon"><i class="ri-global-line"></i></div><h2>AP4 — Foreign Representative Office</h2><p>For a foreign mass media service establishing or operating a representative office in Zimbabwe.</p><span class="registration-action">Start AP4 application <i class="ri-arrow-right-line"></i></span></div></a></div>
  </div>
</div>
@endsection
