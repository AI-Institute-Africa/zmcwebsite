@extends('layouts.portal')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5 px-3" style="background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10 col-xl-8">
        <div class="text-center mb-5">
          <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo" style="height: 80px; margin-bottom: 1.5rem;">
          <h1 class="fw-bold text-dark mb-2" style="font-size: clamp(2rem, 5vw, 2.75rem); letter-spacing: -0.02em;">
            Zimbabwe Media Commission
          </h1>
          <p class="text-muted fw-medium" style="font-size: 1.125rem;">
            Choose your portal to access the right services for you
          </p>
        </div>

        <div class="row g-4">
          {{-- Media Practitioner Card --}}
          <div class="col-12 col-md-6">
            <div class="portal-choice-card h-100 p-4 p-md-5 border-0 shadow-sm" style="background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%); border-radius: 24px; border: 1px solid #eef2f7;">
              <div class="portal-icon mb-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <i class="ri-id-card-line text-warning" style="font-size: 2.5rem;"></i>
              </div>
              <h3 class="fw-bold text-dark mb-2" style="font-size: 1.5rem;">Media Practitioner</h3>
              <p class="text-muted mb-4" style="line-height: 1.7;">
                For individual journalists, freelancers, and media practitioners seeking accreditation, renewals, amendments, and official documentation.
              </p>
              <ul class="list-unstyled mb-4 text-start" style="font-size: 0.95rem; line-height: 2;">
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>New Accreditation Applications (AP3)</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Renewals & Replacements (AP5)</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Amendments & Corrections</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Official Letters & Certificates</li>
              </ul>
              <div class="d-grid gap-2">
                <form method="POST" action="{{ route('public.choose_portal') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="portal" value="journalist">
                  <input type="hidden" name="action" value="login">
                  <button type="submit" class="btn portal-btn w-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 1rem 1.5rem; font-weight: 700; font-size: 1rem; border-radius: 12px; color: #1f2937;">
                    <i class="ri-login-box-line me-2"></i>Sign In as Media Practitioner
                  </button>
                </form>
                <form method="POST" action="{{ route('public.choose_portal') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="portal" value="journalist">
                  <input type="hidden" name="action" value="register">
                  <button type="submit" class="btn portal-btn w-100" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; padding: 1rem 1.5rem; font-weight: 700; font-size: 1rem; border-radius: 12px; color: #1f2937;">
                    <i class="ri-user-add-line me-2"></i>Register as Media Practitioner
                  </button>
                </form>
              </div>
            </div>
          </div>

          {{-- Media House Card --}}
          <div class="col-12 col-md-6">
            <div class="portal-choice-card h-100 p-4 p-md-5 border-0 shadow-sm" style="background: linear-gradient(145deg, #ffffff 0%, #fafbfc 100%); border-radius: 24px; border: 1px solid #eef2f7;">
              <div class="portal-icon mb-4" style="width: 80px; height: 80px; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 20px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <i class="ri-building-4-line text-primary" style="font-size: 2.5rem;"></i>
              </div>
              <h3 class="fw-bold text-dark mb-2" style="font-size: 1.5rem;">Media House</h3>
              <p class="text-muted mb-4" style="line-height: 1.7;">
                For media organizations, broadcasters, publishers, and online platforms registering their business, managing levies, and linking journalists.
              </p>
              <ul class="list-unstyled mb-4 text-start" style="font-size: 0.95rem; line-height: 2;">
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>New Registration (AP1, AP2, AP4)</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Annual Levy Payments</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Journalist Linking & Management</li>
                <li class="mb-2"><i class="ri-check-line text-success me-2"></i>Renewals & Official Letters</li>
              </ul>
              <div class="d-grid gap-2">
                <form method="POST" action="{{ route('public.choose_portal') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="portal" value="mass_media">
                  <input type="hidden" name="action" value="login">
                  <button type="submit" class="btn portal-btn w-100" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; padding: 1rem 1.5rem; font-weight: 700; font-size: 1rem; border-radius: 12px; color: #ffffff;">
                    <i class="ri-login-box-line me-2"></i>Sign In as Media House
                  </button>
                </form>
                <form method="POST" action="{{ route('public.choose_portal') }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="portal" value="mass_media">
                  <input type="hidden" name="action" value="register">
                  <button type="submit" class="btn portal-btn w-100" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; padding: 1rem 1.5rem; font-weight: 700; font-size: 1rem; border-radius: 12px; color: #ffffff;">
                    <i class="ri-user-add-line me-2"></i>Register as Media House
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-5">
          <p class="text-muted small mb-0">
            <i class="ri-shield-check-line me-1"></i>
            Secure access · Email verification required · One account for all ZMC services
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .portal-choice-card {
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
  }
  .portal-choice-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12) !important;
    border-color: rgba(245, 158, 11, 0.2) !important;
  }
  .portal-btn {
    transition: all 0.2s ease;
  }
  .portal-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  }
  .portal-btn:active {
    transform: translateY(0);
  }
</style>
@endsection