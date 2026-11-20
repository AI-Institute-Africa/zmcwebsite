<!-- STAFF SIDEBAR -->
@php
  $role = session('active_staff_role');
  $user = auth()->user();
  $isItDashboard = request()->is('staff/it*');
  $effectiveRole = ($isItDashboard && $role !== 'it_admin') ? 'it_admin' : $role;
  $showItOnly = $isItDashboard || $effectiveRole === 'it_admin';
  $isAdminPanel = in_array($effectiveRole, ['super_admin', 'director'], true) && !$showItOnly;

  $portalTitle = match ($effectiveRole) {
    'accreditation_officer' => 'Accreditation Officer',
    'registration_compliance_officer' => 'Registration & Compliance',
    'accounts_payments' => 'Accounts & Payments',
    'registrar' => 'Registrar',
    'it_admin' => 'IT Administration',
    'super_admin' => 'Super Admin',
    'auditor' => 'System Auditor',
    'production' => 'Production',
    'director' => 'Director Media Development',
    'pr_officer' => 'Public Relations',
    'public_info_compliance' => 'FOIA Appeals',
    'complaints_officer' => 'Complaints Handling',
    'research_training' => 'Complaints Handling',
    default => 'Staff',
  };
@endphp

<div class="vertical-menu" id="zmcSidebar">
  <div class="navbar-brand-box">
    <a href="{{ route('staff.officer.dashboard') }}">
      <img src="{{ asset('zmc_logo.png') }}" alt="ZMC Logo">
    </a>
    <div class="logo-portal-name">
      {{ $portalTitle }}
    </div>
  </div>

  <ul class="sidebar-menu">
    @if(!$showItOnly && !$isAdminPanel && $role === 'accreditation_officer')
      <li class="menu-title">
        Accreditation Officer
      </li>

      <li class="{{ request()->routeIs('staff.officer.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Dashboard</span>
        </a>
      </li>

      <li class="menu-title">
        Applications
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.index') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.applications.index') }}">
          <i class="ri-file-list-3-line"></i> <span>All Applications</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.new-apps') ? 'active' : '' }}"
        style="padding-left: 18px;">
        <a href="{{ route('staff.officer.applications.new-apps') }}"><i class="ri-add-circle-line"></i> <span>New
            Applications</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.renewals') ? 'active' : '' }}"
        style="padding-left: 18px;">
        <a href="{{ route('staff.officer.applications.renewals') }}"><i class="ri-refresh-line"></i> <span>Renewal
            Applications</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.replacements') ? 'active' : '' }}"
        style="padding-left: 18px;">
        <a href="{{ route('staff.officer.applications.replacements') }}"><i class="ri-swap-line"></i>
          <span>Replacement</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.pending') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.applications.pending') }}"><i class="ri-time-line"></i> <span>Pending Accounts
            Review</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.fix-requests') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.fix-requests') }}">
          <i class="ri-tools-line"></i> <span>Returned</span>
          @if(isset($kpis['pending_fix_requests']) && $kpis['pending_fix_requests'] > 0)
            <span class="badge bg-warning text-dark ms-auto">{{ $kpis['pending_fix_requests'] }}</span>
          @endif
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.approved') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.applications.approved') }}"><i class="ri-checkbox-circle-line"></i>
          <span>Approved</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.applications.rejected') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.applications.rejected') }}"><i class="ri-arrow-go-back-line"></i> <span>Returned
            to Applicants</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.processed') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.processed') }}"><i class="ri-stack-line"></i> <span>Processed
            Applications</span></a>
      </li>

      <li class="menu-title">
        Intake
      </li>
      @if(Route::has('staff.officer.physical-intake'))
        <li class="{{ request()->routeIs('staff.officer.physical-intake*') ? 'active' : '' }}">
          <a href="{{ route('staff.officer.physical-intake') }}"><i class="ri-walk-line"></i> <span>Physical
              Intake</span></a>
        </li>
      @endif

      <li class="menu-title">
        Records
      </li>
      <li class="{{ request()->routeIs('staff.officer.records.journalists') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.records.journalists') }}"><i class="ri-id-card-line"></i> <span>Accredited Media
            Practitioners</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.records.mediahouses') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.records.mediahouses') }}"><i class="ri-building-2-line"></i> <span>Registered
            Media Houses</span></a>
      </li>

      <li class="menu-title">
        Document Verification
      </li>
      <li class="{{ request()->routeIs('staff.officer.documents.uploaded') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.documents.uploaded') }}"><i class="ri-folder-2-line"></i> <span>Uploaded
            Documents</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.documents.pending') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.documents.pending') }}"><i class="ri-hourglass-line"></i> <span>Pending
            Verification</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.documents.verified') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.documents.verified') }}"><i class="ri-verified-badge-line"></i>
          <span>Verified</span></a>
      </li>

      <li class="menu-title">
        Design Studio
      </li>
      <li class="{{ request()->routeIs('staff.design-studio.*') ? 'active' : '' }}">
        <a href="{{ route('staff.design-studio.dashboard') }}">
          <i class="ri-palette-line"></i> <span>Design Studio</span>
        </a>
      </li>

    @endif

    @if(!$showItOnly && !$isAdminPanel && $role === 'accounts_payments')
      <li class="menu-title">
        Accounts / Payments
      </li>

      <li class="{{ request()->routeIs('staff.accounts.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Overview</span>
        </a>
      </li>

      <li class="menu-title">
        Payments
      </li>
      <li class="{{ request()->routeIs('staff.accounts.paynow.transactions') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.paynow.transactions') }}">
          <i class="ri-bank-card-line"></i> <span>Paid via PayNow</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.reconciliation') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.reconciliation') }}">
          <i class="ri-git-merge-line"></i> <span>Payment Reconciliation</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.rbz-rate*') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.rbz-rate') }}">
          <i class="ri-exchange-dollar-line"></i> <span>RBZ Exchange Rate</span>
        </a>
      </li>

      <li class="menu-title">
        Fee Verifications
      </li>
      <li class="{{ request()->routeIs('staff.accounts.app-fee-verifications') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.app-fee-verifications') }}">
          <i class="ri-time-line"></i> <span>App Fee Verifications</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.reg-fee-verifications') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.reg-fee-verifications') }}">
          <i class="ri-checkbox-circle-line"></i> <span>Reg Fee Verifications</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.proofs.approved') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.proofs.approved') }}">
          <i class="ri-checkbox-circle-line"></i> <span>Approved Proofs</span>
        </a>
      </li>

      <li class="menu-title">
        Waivers
      </li>
      <li class="{{ request()->routeIs('staff.accounts.waivers.requests') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.waivers.requests') }}">
          <i class="ri-inbox-line"></i> <span>Waiver Requests</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.waivers.approved') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.waivers.approved') }}">
          <i class="ri-check-double-line"></i> <span>Approved Waivers</span>
        </a>
      </li>
      --}}

      <li class="menu-title">
        Applications
      </li>
      {{-- Removed 'Approved (Paid)' per request --}}
      {{--
      <li class="{{ request()->routeIs('staff.accounts.apps.paid') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.apps.paid') }}">
          <i class="ri-shield-check-line"></i> <span>Approved (Paid)</span>
        </a>
      </li>
      --}}
      <li class="{{ request()->routeIs('staff.accounts.apps.pending') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.apps.pending') }}">
          <i class="ri-hourglass-line"></i> <span>Pending Payments</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.apps.waived') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.apps.waived') }}">
          <i class="ri-price-tag-3-line"></i> <span>Waived Applications</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.processed') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.processed') }}">
          <i class="ri-stack-line"></i> <span>Processed Applications</span>
        </a>
      </li>

      <li class="menu-title">
        Renewals
      </li>
      <li class="{{ request()->routeIs('staff.accounts.renewals.*') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.renewals.queue') }}">
          <i class="ri-refresh-line"></i> <span>Renewals Queue</span>
        </a>
      </li>

      <li class="menu-title">
        Reports
      </li>
      <li class="{{ request()->routeIs('staff.accounts.reports.revenue') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.reports.revenue') }}">
          <i class="ri-funds-line"></i> <span>Revenue Reports</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.reports.exceptions') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.reports.exceptions') }}">
          <i class="ri-error-warning-line"></i> <span>Payment Exceptions</span>
        </a>
      </li>

      <li class="menu-title">
        Tools
      </li>
      <li class="{{ request()->routeIs('staff.accounts.alerts') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.alerts') }}">
          <i class="ri-alarm-warning-line"></i> <span>Alerts</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.tools.paynow') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.tools.paynow') }}">
          <i class="ri-settings-3-line"></i> <span>PayNow Settings</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.accounts.help') ? 'active' : '' }}">
        <a href="{{ route('staff.accounts.help') }}">
          <i class="ri-question-line"></i> <span>Help & Support</span>
        </a>
      </li>

    @endif

    @if(!$showItOnly && !$isAdminPanel && $role === 'registrar')
      <li class="menu-title">
        Registrar
      </li>

      <li class="{{ request()->routeIs('staff.registrar.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Overview</span>
        </a>
      </li>


      <li
        class="{{ request()->routeIs('staff.registrar.payment-oversight') || request()->routeIs('staff.registrar.payment-detail') ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.payment-oversight') }}">
          <i class="ri-eye-line"></i> <span>Payment Statuses</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.registrar.documents.uploaded') ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.documents.uploaded') }}">
          <i class="ri-folder-2-line"></i> <span>Uploaded Documents</span>
        </a>
      </li>

      <li class="menu-title">
        Applications Management
      </li>
      <li
        class="{{ request()->routeIs('staff.registrar.applications.list') && request()->route('bucket') === 'all' ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.applications.list', ['bucket' => 'all']) }}"><i
            class="ri-file-list-3-line"></i> <span>All Applications</span></a>
      </li>
      <li
        class="{{ request()->routeIs('staff.registrar.applications.list') && (request()->route('bucket') === 'under-review' || !request()->route('bucket')) ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.applications.list', ['bucket' => 'under-review']) }}"><i
            class="ri-time-line"></i> <span>Under Review</span></a>
      </li>
      <li
        class="{{ request()->routeIs('staff.registrar.applications.list') && request()->route('bucket') === 'approved' ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.applications.list', ['bucket' => 'approved']) }}"><i
            class="ri-checkbox-circle-line"></i> <span>Approved</span></a>
      </li>
      <li
        class="{{ request()->routeIs('staff.registrar.applications.list') && request()->route('bucket') === 'returned' ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.applications.list', ['bucket' => 'returned']) }}"><i
            class="ri-arrow-go-back-line"></i> <span>Returned for Correction</span></a>
      </li>

      <li class="menu-title">
        Production Tracking
      </li>
      <li class="{{ request()->routeIs('staff.registrar.processed') ? 'active' : '' }}">
        <a href="{{ route('staff.registrar.processed') }}"><i class="ri-stack-line"></i> <span>Processed
            Applications</span></a>
      </li>

      <li class="menu-title">
        Reports
      </li>
      <li class="{{ request()->routeIs('staff.reports.*') ? 'active' : '' }}">
        <a href="{{ route('staff.reports.index') }}">
          <i class="ri-bar-chart-line"></i> <span>Operational Reports</span>
        </a>
      </li>

      <li class="menu-title">
        Records
      </li>
      <li class="{{ request()->routeIs('staff.officer.records.journalists') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.records.journalists') }}"><i class="ri-id-card-line"></i> <span>Accredited Media
            Practitioners</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.officer.records.mediahouses') ? 'active' : '' }}">
        <a href="{{ route('staff.officer.records.mediahouses') }}"><i class="ri-building-2-line"></i> <span>Registered
            Media Houses</span></a>
      </li>

      <li class="menu-title">
        System Settings
      </li>
      <li class="{{ request()->routeIs('admin.regions.index') ? 'active' : '' }}">
        <a href="{{ route('admin.regions.index') }}"><i class="ri-map-pin-line"></i> <span>Regional Offices</span></a>
      </li>

      <li class="menu-title">
        Design Studio
      </li>
      <li class="{{ request()->routeIs('staff.design-studio.*') ? 'active' : '' }}">
        <a href="{{ route('staff.design-studio.dashboard') }}">
          <i class="ri-palette-line"></i> <span>Design Studio</span>
        </a>
      </li>
    @endif

    @if(!$showItOnly && !$isAdminPanel && in_array($role, ['production', 'accreditation_officer'], true))
      <li class="menu-title">
        Production
      </li>
      <li class="{{ request()->routeIs('staff.production.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.production.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Overview</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.queue') ? 'active' : '' }}">
        <a href="{{ route('staff.production.queue') }}">
          <i class="ri-inbox-2-line"></i> <span>Production Queue</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.cards') ? 'active' : '' }}">
        <a href="{{ route('staff.production.cards') }}">
          <i class="ri-id-card-line"></i> <span>Card Production</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.certificates') ? 'active' : '' }}">
        <a href="{{ route('staff.production.certificates') }}">
          <i class="ri-award-line"></i> <span>Certificate Production</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.printing') ? 'active' : '' }}">
        <a href="{{ route('staff.production.printing') }}">
          <i class="ri-printer-line"></i> <span>Printing Queue</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.issuance') ? 'active' : '' }}">
        <a href="{{ route('staff.production.issuance') }}">
          <i class="ri-hand-heart-line"></i> <span>Issuance & Collection</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.registers.issued') ? 'active' : '' }}">
        <a href="{{ route('staff.production.registers.issued') }}">
          <i class="ri-book-open-line"></i> <span>Issued Register</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.production.reports') ? 'active' : '' }}">
        <a href="{{ route('staff.production.reports') }}">
          <i class="ri-file-chart-line"></i> <span>Reports</span>
        </a>
      </li>

      <li class="menu-title">
        Design Studio
      </li>
      <li class="{{ request()->routeIs('staff.design-studio.*') ? 'active' : '' }}">
        <a href="{{ route('staff.design-studio.dashboard') }}">
          <i class="ri-palette-line"></i> <span>Design Studio</span>
        </a>
      </li>
    @endif

    {{-- Super Admin / Director Oversight Section (hidden on IT dashboard) --}}
    @if(!$showItOnly && $user?->hasAnyRole(['super_admin', 'director', 'it_admin']))
      @php
        $c = $admin_sidebar_counts ?? [
          'mediahouse_total' => 0,
          'accreditation_total' => 0,
          'pending_total' => 0,
          'pending_mediahouse' => 0,
          'pending_accreditation' => 0,
        ];
      @endphp

      <li class="menu-title">
        System Oversight & Admin
      </li>

      <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a href="{{ route('admin.dashboard') }}">
          <i class="ri-dashboard-3-line"></i>
          <span>Dashboard</span>
          @if(($c['pending_total'] ?? 0) > 0)
            <span class="badge bg-warning text-dark" style="margin-left:auto;">{{ $c['pending_total'] }}</span>
          @endif
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.superadmin.applications.index') ? 'active' : '' }}">
        <a href="{{ route('staff.superadmin.applications.index') }}">
          <i class="ri-inbox-archive-line"></i>
          <span>Applications Queue</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
        <a href="{{ route('admin.analytics') }}">
          <i class="ri-line-chart-line"></i> <span>Analytics</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.mediahouse.*') ? 'active' : '' }}">
        <a href="{{ route('admin.mediahouse.index') }}">
          <i class="ri-building-2-line"></i>
          <span>Media House Registrations</span>
          <span class="badge bg-light text-dark" style="margin-left:auto;">{{ $c['mediahouse_total'] ?? 0 }}</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.accreditation.*') ? 'active' : '' }}">
        <a href="{{ route('admin.accreditation.index') }}">
          <i class="ri-id-card-line"></i>
          <span>Media Practitioners Accreditation</span>
          <span class="badge bg-light text-dark" style="margin-left:auto;">{{ $c['accreditation_total'] ?? 0 }}</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}">
        <a href="{{ route('admin.approvals.index') }}">
          <i class="ri-user-follow-line"></i> <span>User Approvals</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.content.*') ? 'active' : '' }}">
        <a
          href="{{ Route::has('admin.content.index') ? route('admin.content.index') : (Route::has('content.index') ? route('content.index') : '#') }}">
          <i class="ri-megaphone-line"></i> <span>{{ __("Content") }}</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
        <a href="{{ route('admin.news.index') }}">
          <i class="ri-newspaper-line"></i> <span>{{ __("News") }}</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}">
        <a href="{{ route('admin.complaints.index') }}">
          <i class="ri-chat-1-line"></i> <span>{{ __("Complaints") }}</span>
        </a>
      </li>


      <li class="{{ request()->routeIs('admin.downloads.*') ? 'active' : '' }}">
        <a href="{{ route('admin.downloads.index') }}">
          <i class="ri-download-2-line"></i> <span>{{ __("Downloads") }}</span>
        </a>
      </li>

      <li class="menu-title">
        Accreditation Performance
      </li>
      @if(Route::has('staff.director.reports.staff'))
        <li class="{{ request()->routeIs('staff.director.reports.staff') ? 'active' : '' }}">
          <a href="{{ route('staff.director.reports.staff') }}"><i class="ri-line-chart-line"></i> <span>Accreditation
              Performance</span></a>
        </li>
      @endif

      <li class="menu-title">
        System Oversight
      </li>
      <li class="zmc-has-submenu">
        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-between"
          onclick="(function(t){var sub=t.nextElementSibling;var willOpen=sub.classList.contains('d-none');document.querySelectorAll('.zmc-has-submenu > ul').forEach(function(u){u.classList.add('d-none');});if(willOpen){sub.classList.remove('d-none');}})(this)">
          <span><i class="ri-group-line me-2"></i> User Accounts & Management</span>
          <i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="list-unstyled ps-4 mt-2 {{ request()->routeIs('admin.users.*') ? '' : 'd-none' }}"
          style="font-size: 11px;">
          <li class="mb-2">
            <a href="{{ route('admin.users.index') }}"
              class="text-decoration-none {{ request()->routeIs('admin.users.index') ? 'text-warning fw-bold' : 'text-white-50' }}">
              <i class="ri-list-check me-1"></i> Overview
            </a>
          </li>
          <li class="mb-2">
            <a href="{{ route('admin.users.staff') }}"
              class="text-decoration-none {{ request()->routeIs('admin.users.staff') ? 'text-warning fw-bold' : 'text-white-50' }}">
              <i class="ri-user-star-line me-1"></i> Staff Accounts
            </a>
          </li>
          <li class="mb-2">
            <a href="{{ route('admin.users.public') }}"
              class="text-decoration-none {{ request()->routeIs('admin.users.public') ? 'text-warning fw-bold' : 'text-white-50' }}">
              <i class="ri-user-heart-line me-1"></i> Public Users
            </a>
          </li>
        </ul>
      </li>
      <li class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
        <a href="{{ route('admin.audit.index') }}">
          <i class="ri-file-list-3-line"></i> <span>System Audit Logs</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('admin.users.login_activity') ? 'active' : '' }}">
        <a href="{{ route('admin.users.login_activity') }}">
          <i class="ri-login-box-line"></i> <span>User Login Trails</span>
        </a>
      </li>

      <li class="menu-title">
        Design Studio
      </li>
      <li class="{{ request()->routeIs('staff.design-studio.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.design-studio.dashboard') }}">
          <i class="ri-palette-line"></i> <span>Design Studio</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.design-studio.index') ? 'active' : '' }}">
        <a href="{{ route('staff.design-studio.index', ['status' => 'approved']) }}">
          <i class="ri-check-double-line"></i> <span>Active Templates</span>
        </a>
      </li>
    @endif

    @if($showItOnly || $role === 'it_admin')
      <li class="menu-title">
        IT Administration
      </li>
      <li class="{{ request()->routeIs('staff.it.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.it.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.it.monitoring') ? 'active' : '' }}">
        <a href="{{ route('staff.it.monitoring') }}"><i class="ri-pulse-line"></i> <span>System Monitoring</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.incidents') ? 'active' : '' }}">
        <a href="{{ route('staff.it.incidents') }}"><i class="ri-alarm-warning-line"></i> <span>Alerts &
            Incidents</span></a>
      </li>

      <li class="menu-title">
        User & Access
      </li>
      <li class="{{ request()->routeIs('staff.it.users-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.users-mgmt') }}"><i class="ri-user-settings-line"></i> <span>User
            Management</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.roles-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.roles-mgmt') }}"><i class="ri-shield-user-line"></i> <span>Roles</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.roles-permissions*') ? 'active' : '' }}">
        <a href="{{ route('staff.it.roles-permissions') }}"><i class="ri-shield-keyhole-line"></i> <span>Roles &
            Permissions</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.temp-roles') ? 'active' : '' }}">
        <a href="{{ route('staff.it.temp-roles') }}"><i class="ri-swap-line"></i> <span>Temporary Roles</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.locations-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.locations-mgmt') }}"><i class="ri-map-pin-line"></i> <span>Regions &
            Locations</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.security-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.security-mgmt') }}"><i class="ri-lock-password-line"></i> <span>Access
            Control</span></a>
      </li>

      <li class="menu-title">
        Configuration
      </li>
      <li class="{{ request()->routeIs('staff.it.form-builder*') ? 'active' : '' }}">
        <a href="{{ route('staff.it.form-builder') }}"><i class="ri-file-edit-line"></i> <span>Form Builder</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.dashboard-builder*') ? 'active' : '' }}">
        <a href="{{ route('staff.it.dashboard-builder') }}"><i class="ri-layout-masonry-line"></i> <span>Dashboard
            Builder</span></a>
      </li>

      <li class="menu-title">
        Operations
      </li>
      @if(auth()->user()?->hasRole('it_admin') && !auth()->user()?->hasRole('super_admin'))
        <li class="{{ request()->routeIs('staff.it.helpdesk') ? 'active' : '' }}">
          <a href="{{ route('staff.it.helpdesk') }}"><i class="ri-customer-service-2-line"></i> <span>Helpdesk /
              Support</span></a>
        </li>
      @endif

      <li class="menu-title">
        System Health
      </li>
      @if(auth()->user()?->hasRole('it_admin') && !auth()->user()?->hasRole('super_admin'))
        <li class="{{ request()->routeIs('staff.it.devices') ? 'active' : '' }}">
          <a href="{{ route('staff.it.devices') }}"><i class="ri-printer-line"></i> <span>Printers & Devices</span></a>
        </li>
      @endif
      <li class="{{ request()->routeIs('staff.it.performance') ? 'active' : '' }}">
        <a href="{{ route('staff.it.performance') }}"><i class="ri-speed-line"></i> <span>System Performance</span></a>
      </li>

      <li class="menu-title">
        Audit & Reports
      </li>
      <li class="{{ request()->routeIs('staff.it.audit-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.audit-mgmt') }}"><i class="ri-list-check-2"></i> <span>Audit Trail</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.reports-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.reports-mgmt') }}"><i class="ri-bar-chart-boxed-line"></i> <span>Reports</span></a>
      </li>

      <li class="menu-title">
        Configuration
      </li>
      <li class="{{ request()->routeIs('staff.it.system-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.system-mgmt') }}"><i class="ri-settings-3-line"></i> <span>Settings</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.it.backup-mgmt') ? 'active' : '' }}">
        <a href="{{ route('staff.it.backup-mgmt') }}"><i class="ri-hard-drive-2-line"></i> <span>Backups</span></a>
      </li>
    @endif

    @if(!$showItOnly && !$isAdminPanel && $role === 'auditor')
      <li class="menu-title" style="margin-top:14px;">AUDITOR</li>

      <li class="{{ request()->routeIs('staff.auditor.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.dashboard') }}">
          <i class="ri-eye-line"></i> <span>Dashboard</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.analytics') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.analytics') }}">
          <i class="ri-line-chart-line"></i> <span>Analytics</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.logs') || request()->routeIs('staff.auditor.logins') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.logs') }}">
          <i class="ri-login-box-line"></i> <span>User Access & Login Monitoring</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.applications') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.applications') }}">
          <i class="ri-file-search-line"></i> <span>Application Audits</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.reconciliation') || request()->routeIs('staff.auditor.paynow') || request()->routeIs('staff.auditor.proofs') || request()->routeIs('staff.auditor.waivers') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.reconciliation') }}">
          <i class="ri-exchange-dollar-line"></i> <span>Payments & Reconciliations</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.reports*') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.reports') }}">
          <i class="ri-bar-chart-2-line"></i> <span>Reports</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.flags*') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.flags') }}">
          <i class="ri-flag-2-line"></i> <span>Flags & Findings</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.auditor.complaints') ? 'active' : '' }}">
        <a href="{{ route('staff.auditor.complaints') }}">
          <i class="ri-chat-check-line"></i> <span>Complaints & Appeals Oversight</span>
        </a>
      </li>

    @endif

    @if(!$showItOnly && $role === 'director')
      <li class="menu-title"
        style="padding:10px 18px; font-size:var(--font-size-sm); letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.55);">
        Director MDG Strategic Oversight
      </li>
      <li class="{{ request()->routeIs('staff.director.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.director.dashboard') }}"><i class="ri-pulse-line"></i> <span>Overview</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.applications.index') ? 'active' : '' }}">
        <a href="{{ route('staff.director.applications.index') }}"><i class="ri-inbox-archive-line"></i> <span>Applications Queue</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.accreditation') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.accreditation') }}"><i class="ri-bar-chart-2-line"></i>
          <span>Accreditation Performance</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.financial') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.financial') }}"><i class="ri-money-dollar-box-line"></i> <span>Financial
            Overview</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.compliance') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.compliance') }}"><i class="ri-shield-flash-line"></i> <span>Compliance &
            Risk</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.mediahouses') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.mediahouses') }}"><i class="ri-building-line"></i> <span>Media House
            Oversight</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.issuance') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.issuance') }}"><i class="ri-printer-cloud-line"></i> <span>Issuance &
            Printing</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.geographic') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.geographic') }}"><i class="ri-map-pin-line"></i> <span>Geographic
            Distribution</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.director.reports.downloads') ? 'active' : '' }}">
        <a href="{{ route('staff.director.reports.downloads') }}"><i class="ri-download-cloud-2-line"></i> <span>Reports &
            Downloads</span></a>
      </li>
    @endif

    {{-- PR Officer Section --}}
    @if(!$showItOnly && !$isAdminPanel && $role === 'pr_officer')
      <li class="menu-title">
        Public Relations
      </li>
      <li class="{{ request()->routeIs('staff.pr.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.pr.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.dashboard') && request('tab') === 'notices' ? 'active' : '' }}">
        <a href="{{ route('staff.pr.dashboard', ['tab' => 'notices']) }}"><i class="ri-megaphone-line"></i> <span>Notices
            & Events</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.dashboard') && request('tab') === 'news' ? 'active' : '' }}">
        <a href="{{ route('staff.pr.dashboard', ['tab' => 'news']) }}"><i class="ri-newspaper-line"></i> <span>News /
            Press Statements</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.dashboard') && request('tab') === 'tenders' ? 'active' : '' }}">
        <a href="{{ route('staff.pr.dashboard', ['tab' => 'tenders']) }}"><i class="ri-file-text-line"></i>
          <span>Tenders</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.dashboard') && request('tab') === 'vacancies' ? 'active' : '' }}">
        <a href="{{ route('staff.pr.dashboard', ['tab' => 'vacancies']) }}"><i class="ri-briefcase-line"></i>
          <span>Vacancies</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.website-people.*') ? 'active' : '' }}">
        <a href="{{ route('staff.pr.website-people.index') }}"><i class="ri-team-line"></i> <span>Commissioners & Secretariat</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.pr.messages.*') ? 'active' : '' }}">
        <a href="{{ route('staff.pr.messages.index') }}"><i class="ri-mail-line"></i> <span>Website Messages</span></a>
      </li>
    @endif

    @if(!$showItOnly && !$isAdminPanel && $role === 'registration_compliance_officer')
      <li class="menu-title">
        Registration Compliance
      </li>
      <li class="{{ request()->routeIs('staff.registration-compliance.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.registration-compliance.dashboard') }}">
          <i class="ri-dashboard-3-line"></i><span>Compliance Dashboard</span>
        </a>
      </li>
      <li class="zmc-has-submenu">
        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-between" onclick="(function(t){var sub=t.nextElementSibling;var willOpen=sub.classList.contains('d-none');document.querySelectorAll('.zmc-has-submenu > ul').forEach(function(u){u.classList.add('d-none');});if(willOpen){sub.classList.remove('d-none');}})(this)">
          <span><i class="ri-database-2-line me-2"></i> Registers</span><i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="list-unstyled ps-4 mt-2 {{ request()->routeIs('staff.registration-compliance.registers.*') ? '' : 'd-none' }}" style="font-size:11px;">
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.registers.media-houses') }}" class="text-decoration-none text-white-50">Media Houses</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.registers.local-practitioners') }}" class="text-decoration-none text-white-50">Local Practitioners</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.registers.foreign-practitioners') }}" class="text-decoration-none text-white-50">Foreign Practitioners</a></li>
        </ul>
      </li>
      <li class="zmc-has-submenu">
        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-between" onclick="(function(t){var sub=t.nextElementSibling;var willOpen=sub.classList.contains('d-none');document.querySelectorAll('.zmc-has-submenu > ul').forEach(function(u){u.classList.add('d-none');});if(willOpen){sub.classList.remove('d-none');}})(this)">
          <span><i class="ri-shield-check-line me-2"></i> Compliance</span><i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="list-unstyled ps-4 mt-2 {{ request()->routeIs('staff.registration-compliance.compliance.*') ? '' : 'd-none' }}" style="font-size:11px;">
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.renewals-expiries') }}" class="text-decoration-none text-white-50">Renewals &amp; Expiries</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.annual-levies') }}" class="text-decoration-none text-white-50">Annual Levies</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.reminders-notices') }}" class="text-decoration-none text-white-50">Reminders &amp; Notices</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.non-compliant') }}" class="text-decoration-none text-white-50">Non-compliant Cases</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.compliant') }}" class="text-decoration-none text-white-50">Compliant Cases</a></li>
          <li class="mb-2"><a href="{{ route('staff.registration-compliance.compliance.inspections') }}" class="text-decoration-none text-white-50">Inspections &amp; Enforcement</a></li>
        </ul>
      </li>
      <li class="{{ request()->routeIs('staff.registration-compliance.analytics') ? 'active' : '' }}"><a href="{{ route('staff.registration-compliance.analytics') }}"><i class="ri-bar-chart-2-line"></i><span>Reports &amp; Analytics</span></a></li>
      <li class="{{ request()->routeIs('staff.registration-compliance.settings') ? 'active' : '' }}"><a href="{{ route('staff.registration-compliance.settings') }}"><i class="ri-settings-3-line"></i><span>Settings</span></a></li>
    @endif

    {{-- PUBLIC INFORMATION COMPLIANCE Section --}}
    @if(!$showItOnly && !$isAdminPanel && $role === 'public_info_compliance')
      <li class="menu-title">
        Public Information Compliance
      </li>
      
      <li class="{{ request()->routeIs('staff.appeals.dashboard') ? 'active' : '' }}">
        <a href="{{ route('staff.appeals.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>FOIA Appeals Management</span>
        </a>
      </li>

      <li class="zmc-has-submenu">
        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-between"
          onclick="(function(t){var sub=t.nextElementSibling;var willOpen=sub.classList.contains('d-none');document.querySelectorAll('.zmc-has-submenu > ul').forEach(function(u){u.classList.add('d-none');});if(willOpen){sub.classList.remove('d-none');}})(this)">
          <span><i class="ri-scales-3-line me-2"></i> Appeals</span>
          <i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="list-unstyled ps-4 mt-2 {{ request()->routeIs('staff.appeals.index') ? '' : 'd-none' }}" style="font-size: 11px;">
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index') }}" class="text-decoration-none text-white-50">All Appeals</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index', ['status' => 'open']) }}" class="text-decoration-none text-white-50">New Appeals</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index', ['status' => 'under_review']) }}" class="text-decoration-none text-white-50">Under Review</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index', ['status' => 'awaiting_information']) }}" class="text-decoration-none text-white-50">Awaiting Information</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index', ['status' => 'response_sent']) }}" class="text-decoration-none text-white-50">Responded</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.index', ['status' => 'closed']) }}" class="text-decoration-none text-white-50">Closed</a>
          </li>
        </ul>
      </li>

      <li class="zmc-has-submenu">
        <a href="javascript:void(0)" class="d-flex align-items-center justify-content-between"
          onclick="(function(t){var sub=t.nextElementSibling;var willOpen=sub.classList.contains('d-none');document.querySelectorAll('.zmc-has-submenu > ul').forEach(function(u){u.classList.add('d-none');});if(willOpen){sub.classList.remove('d-none');}})(this)">
          <span><i class="ri-bank-card-line me-2"></i> Payments</span>
          <i class="ri-arrow-down-s-line"></i>
        </a>
        <ul class="list-unstyled ps-4 mt-2 {{ request()->routeIs('staff.appeals.payments') ? '' : 'd-none' }}" style="font-size: 11px;">
          <li class="mb-2">
            <a href="{{ route('staff.appeals.payments', ['status' => 'pending']) }}" class="text-decoration-none text-white-50">Pending Payments</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.payments', ['status' => 'paid']) }}" class="text-decoration-none text-white-50">Successful Payments</a>
          </li>
          <li class="mb-2">
            <a href="{{ route('staff.appeals.payments', ['status' => 'failed']) }}" class="text-decoration-none text-white-50">Failed Payments</a>
          </li>
        </ul>
      </li>

      <li class="{{ request()->routeIs('staff.appeals.reports') ? 'active' : '' }}">
        <a href="{{ route('staff.appeals.reports') }}">
          <i class="ri-bar-chart-box-line"></i> <span>Reports & Analytics</span>
        </a>
      </li>

      <li class="{{ request()->routeIs('staff.appeals.settings') ? 'active' : '' }}">
        <a href="{{ route('staff.appeals.settings') }}">
          <i class="ri-settings-3-line"></i> <span>Settings</span>
        </a>
      </li>
    @endif

    {{-- Complaints Handling Section --}}
    @if(!$showItOnly && !$isAdminPanel && in_array($role, ['research_training', 'complaints_officer'], true))
      <li class="menu-title">
        Complaints Handling
      </li>
      <li class="{{ request()->routeIs('staff.research.dashboard') ? 'active' : '' }}">
        <a
          href="{{ Route::has('staff.research.dashboard') ? route('staff.research.dashboard') : route('staff.officer.dashboard') }}">
          <i class="ri-dashboard-3-line"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="{{ request()->routeIs('staff.research.register') ? 'active' : '' }}">
        <a href="{{ route('staff.research.register') }}"><i class="ri-booklet-line"></i> <span>Complaints Register</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.research.reports') ? 'active' : '' }}">
        <a href="{{ route('staff.research.reports') }}"><i class="ri-bar-chart-2-line"></i> <span>Reports &amp; Analytics</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.research.downloads') || request()->routeIs('staff.research.download*') ? 'active' : '' }}">
        <a href="{{ route('staff.research.downloads') }}"><i class="ri-download-2-line"></i> <span>Downloads</span></a>
      </li>
      <li class="{{ request()->routeIs('staff.research.settings') ? 'active' : '' }}">
        <a href="{{ route('staff.research.settings') }}"><i class="ri-settings-3-line"></i> <span>Settings</span></a>
      </li>
    @endif

    {{-- Let staff switch only between dashboards assigned to their account. --}}
    @if(!$user?->hasRole('super_admin') && count($user?->getRoleNames() ?? []) > 1)
      <li class="{{ request()->routeIs('staff.roles.switch') ? 'active' : '' }}">
        <a href="{{ route('staff.roles.switch') }}">
          <i class="ri-shuffle-line"></i> <span>Switch Dashboard</span>
        </a>
      </li>
    @endif
  </ul>

  <div class="sidebar-user">
    <img src="https://ui-avatars.com/api/?name={{ urlencode($user?->name ?? 'User') }}&background=facc15&color=000"
      alt="user">
    <div style="line-height:1.1;">
      <div style="font-weight:700;font-size:var(--font-size-sm);color:#fff;">
        {{ $user?->name ?? $user?->email }}
      </div>
      <div style="font-size:10px;color:rgba(255,255,255,0.7);">
        {{ $user?->designation ?? ($effectiveRole ? strtoupper(str_replace('_', ' ', $effectiveRole)) : 'STAFF') }}
        @if(!empty($user?->region)) • {{ strtoupper($user->region) }} @endif
      </div>
    </div>
  </div>
</div>
