<!-- TOPBAR -->
@php
  $isStaff = request()->is('staff') || request()->is('staff/*') || str_starts_with(request()->path(), 'staff');
  $logoutRoute = $isStaff ? route('staff.logout') : route('logout');

  $u = auth()->user();
  $canSwitchApplicantProfile = !$isStaff && $u && $u->isMediaPractitioner() && $u->isMediaHouseStaff();
  $currentApplicantPortal = request()->routeIs('mediahouse.*') ? 'mediahouse' : 'accreditation';
  $nextApplicantPortal = $currentApplicantPortal === 'mediahouse' ? 'accreditation' : 'mediahouse';
  $unreadCount = 0;
  $latestMsgs = collect();
  if ($u && class_exists(\App\Models\ApplicationMessage::class)) {
    $unreadCount = \App\Models\ApplicationMessage::where('to_user_id', $u->id)->whereNull('read_at')->count();
    $latestMsgs = \App\Models\ApplicationMessage::with(['application'])
      ->where(function($q) use ($u){ $q->where('from_user_id',$u->id)->orWhere('to_user_id',$u->id); })
      ->orderByDesc('sent_at')->orderByDesc('id')
      ->take(5)
      ->get();
  }

@endphp

<header class="topbar">
  <div class="topbar-row--main">

    <!-- Left Section: Toggle Only -->
    <div class="topbar-left">
      <button class="topbar-toggle" type="button" id="sidebarToggle" aria-label="Toggle menu">
        <i class="ri-menu-2-line"></i>
      </button>
      @if($isStaff && \Illuminate\Support\Facades\Route::has('staff.search'))
        <form class="zmc-search" action="{{ route('staff.search') }}" method="GET" role="search">
          <i class="ri-search-line" aria-hidden="true"></i>
          <input id="zmcGlobalSearch" type="search" name="q" value="{{ request()->routeIs('staff.search') ? request('q') : '' }}" placeholder="Search by reference or name" aria-label="Search applications by reference or name" autocomplete="off">
          <kbd aria-hidden="true">/</kbd>
        </form>
      @endif
    </div>

    <!-- Right Section: Utility Actions -->
    <div class="topbar-right">

      <div class="d-flex align-items-center gap-2">
        <!-- Utility actions -->
      </div>

      @if($canSwitchApplicantProfile)
        <form method="POST" action="{{ route('portal.switch-profile') }}" class="d-inline">
          @csrf
          <input type="hidden" name="portal" value="{{ $nextApplicantPortal }}">
          <button class="btn btn-sm btn-outline-secondary me-2" type="submit" title="Switch to your other ZMC profile">
            <i class="ri-shuffle-line me-1"></i>Switch to {{ $nextApplicantPortal === 'mediahouse' ? 'Media House' : 'Media Practitioner' }}
          </button>
        </form>
      @endif

      <!-- MESSAGES DROPDOWN -->
      <div class="dropdown">
        <button class="icon-btn dropdown-toggle hide-caret" type="button" id="messageDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Messages">
          <i class="ri-mail-line"></i>
          @if($unreadCount > 0)
            <span class="icon-badge badge-blue">{{ $unreadCount }}</span>
          @endif
        </button>
        <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-0 mt-2" aria-labelledby="messageDropdown" style="width: 300px; border-radius: 12px; overflow: hidden;">
          <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
            <h1 class="m-0 fw-bold" style="font-size: var(--font-size-xs);">Messages</h1>
            @if($unreadCount > 0)
              <span class="badge bg-primary-subtle text-primary small">{{ $unreadCount }} New</span>
            @else
              <span class="badge bg-primary-subtle text-primary small">Up to date</span>
            @endif
          </div>
          <div class="message-scroll" style="max-height: 250px; overflow-y: auto;">
            @forelse($latestMsgs as $msg)
              @php
                $isUnread = $u && (int)$msg->to_user_id === (int)$u->id && empty($msg->read_at);
                $otherId = $u ? ((int)$msg->from_user_id === (int)$u->id ? $msg->to_user_id : $msg->from_user_id) : null;
                $other = $otherId ? \App\Models\User::find($otherId) : null;
                $who = $other?->name ?? 'User';
                $ref = $msg->application?->reference ?? ('APP-' . $msg->application_id);
                $when = $msg->sent_at ? $msg->sent_at->diffForHumans() : ($msg->created_at?->diffForHumans() ?? '');
              @endphp
              <a href="{{ route('messages.thread', $msg->application_id) }}" class="dropdown-item p-3 border-bottom d-flex align-items-start gap-3 {{ $isUnread ? 'bg-light' : '' }}">
                <div class="flex-shrink-0 bg-warning-subtle text-warning p-2 rounded-circle" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                  <i class="ri-message-2-line"></i>
                </div>
                <div class="flex-grow-1">
                  <p class="mb-0 fw-bold small text-dark">{{ $who }} <span class="text-muted" style="font-weight:800;">• {{ $ref }}</span></p>
                  <p class="mb-0 text-muted small">{{ \Illuminate\Support\Str::limit($msg->body, 60) }}</p>
                  <span class="text-muted" style="font-size: var(--font-size-xs);">{{ $when }}</span>
                </div>
              </a>
            @empty
              <div class="p-3 text-muted small">No messages yet.</div>
            @endforelse
          </div>
          <a href="{{ route('messages.index') }}" class="dropdown-item text-center p-2 bg-light small fw-bold text-primary">View All Messages</a>
        </div>
      </div>

      <!-- THEME TOGGLE -->
       <button class="icon-btn theme-toggle-btn me-2" type="button" onclick="toggleZMCTheme()" title="Toggle Light/Dark Mode">
         <i class="ri-moon-line" id="themeIcon"></i>
       </button>
 
       <!-- Logout Pill -->
       <button class="logout-pill" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" type="button">
         <i class="ri-logout-box-r-line"></i>
         <span>LOGOUT</span>
       </button>
      <form id="logout-form" action="{{ $logoutRoute }}" method="POST" class="d-none">@csrf</form>

    </div>
  </div>
</header>

<script>
  function toggleZMCTheme() {
    const htmlEl = document.documentElement;
    const isDark = htmlEl.getAttribute('data-bs-theme') === 'dark';
    const newTheme = isDark ? 'light' : 'dark';
    const button = document.querySelector('.theme-toggle-btn');
    if (!button || button.disabled) return;
    button.disabled = true;

    fetch(@json(route('settings.theme.ajax')), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ theme: newTheme })
    }).then(response => {
      if (!response.ok) throw new Error('Theme preference could not be saved.');
      return response.json();
    }).then(() => {
      htmlEl.setAttribute('data-bs-theme', newTheme);
      const iconEl = document.getElementById('themeIcon');
      if (iconEl) iconEl.className = newTheme === 'dark' ? 'ri-sun-line' : 'ri-moon-line';
      window.dispatchEvent(new CustomEvent('zmc:theme-changed', { detail: newTheme }));
    }).catch(error => {
      console.error(error);
      window.alert('Could not save your appearance preference. Please try again.');
    }).finally(() => { button.disabled = false; });
  }

  // Initialize correct icon on load
  document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const iconEl = document.getElementById('themeIcon');
    if (iconEl && isDark) {
      iconEl.classList.remove('ri-moon-line');
      iconEl.classList.add('ri-sun-line');
    }
  });
</script>
