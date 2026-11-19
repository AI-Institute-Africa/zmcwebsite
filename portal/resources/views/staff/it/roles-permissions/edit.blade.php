@extends('layouts.portal')

@section('title', 'Edit Role: ' . $role->name)

@section('content')
<style>
    .permission-editor { --permission-ink: #172033; --permission-muted: #64748b; --permission-line: #e5eaf1; }
    .permission-topbar, .permission-panel, .assigned-panel { border: 1px solid var(--permission-line); border-radius: 16px; background: #fff; box-shadow: 0 8px 24px rgba(15, 23, 42, .045); }
    .permission-role-mark { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; border-radius: 13px; background: #fff7db; color: #9a6700; font-size: 21px; }
    .permission-summary { color: var(--permission-muted); font-size: 13px; }
    .permission-group { overflow: hidden; border: 1px solid var(--permission-line); border-radius: 13px; background: #fff; }
    .permission-group + .permission-group { margin-top: 14px; }
    .permission-group-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--permission-line); background: #f8fafc; }
    .permission-group-title { color: var(--permission-ink); font-size: 13px; font-weight: 750; text-transform: capitalize; }
    .permission-group-count { color: var(--permission-muted); font-size: 12px; white-space: nowrap; }
    .permission-group-actions { display: flex; gap: 10px; }
    .permission-group-actions button { padding: 2px 0; border: 0; background: transparent; color: #475569; font-size: 12px; font-weight: 650; }
    .permission-group-actions button:hover { color: #8a5b00; text-decoration: underline; }
    .permission-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; padding: 14px; }
    .permission-option { position: relative; display: flex; align-items: flex-start; gap: 11px; min-width: 0; margin: 0; padding: 12px; border: 1px solid var(--permission-line); border-radius: 11px; background: #fff; cursor: pointer; transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease; }
    .permission-option:hover { border-color: #cbd5e1; background: #fafcff; }
    .permission-option.is-selected { border-color: #e9b72f; background: #fffbeb; box-shadow: 0 0 0 2px rgba(234, 179, 8, .08); }
    .permission-option input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .permission-check { display: grid; width: 19px; height: 19px; flex: 0 0 auto; place-items: center; margin-top: 1px; border: 1.5px solid #cbd5e1; border-radius: 5px; background: #fff; color: #fff; font-size: 13px; transition: background-color .16s ease, border-color .16s ease; }
    .permission-option input:checked + .permission-check { border-color: #b77900; background: #d99a08; }
    .permission-option input:focus-visible + .permission-check { outline: 3px solid rgba(59, 130, 246, .28); outline-offset: 2px; }
    .permission-copy { display: block; min-width: 0; }
    .permission-copy strong { display: block; color: var(--permission-ink); font-size: 12px; font-weight: 700; line-height: 1.35; }
    .permission-copy code { display: block; overflow-wrap: anywhere; margin-top: 4px; color: #64748b; font-size: 10px; }
    .permission-savebar { position: sticky; bottom: 12px; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 16px; padding: 12px 14px; border: 1px solid var(--permission-line); border-radius: 13px; background: rgba(255,255,255,.96); box-shadow: 0 8px 24px rgba(15, 23, 42, .09); backdrop-filter: blur(8px); }
    .permission-search { min-width: 230px; max-width: 340px; }
    .assigned-user { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid #eef2f6; }
    .assigned-user:last-child { border-bottom: 0; }
    .assigned-user-name { color: #1e293b; font-size: 13px; font-weight: 700; }
    .assigned-user-email { display: block; margin-top: 3px; color: #64748b; font-size: 11px; overflow-wrap: anywhere; }
    @media (max-width: 767.98px) {
        .permission-grid { grid-template-columns: 1fr; padding: 10px; }
        .permission-group-head { align-items: flex-start; flex-direction: column; }
        .permission-savebar { align-items: stretch; flex-direction: column; }
        .permission-savebar .btn { width: 100%; }
        .permission-search { min-width: 0; max-width: none; width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .permission-option, .permission-check { transition: none; }
    }
</style>

<div class="container-fluid px-4 permission-editor">
    @if(session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 mb-3" role="status">
            <i class="ri-checkbox-circle-line" style="font-size:18px;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="ri-error-warning-line" style="font-size:18px;"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="permission-topbar d-flex justify-content-between align-items-center flex-wrap gap-3 p-3 p-md-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="permission-role-mark"><i class="ri-shield-keyhole-line" aria-hidden="true"></i></span>
            <div>
                <div class="text-muted small mb-1">Role access settings</div>
                <h1 class="h4 fw-bold mb-1" style="color:#172033;">{{ ucwords(str_replace('_', ' ', $role->name)) }}</h1>
                <div class="permission-summary">Choose the actions users with this role can perform.</div>
            </div>
        </div>
        <a href="{{ route('staff.it.roles-permissions') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-line me-1"></i> All roles
        </a>
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <section class="permission-panel p-3 p-md-4" aria-labelledby="permissionMatrixTitle">
                <form action="{{ route('staff.it.roles-permissions.update-role', $role) }}" method="POST" id="permissionForm">
                    @csrf
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h2 id="permissionMatrixTitle" class="h5 fw-bold mb-1" style="color:#172033;">Permissions</h2>
                            <div class="permission-summary">{{ $permissions->count() }} available across {{ $permissionGroups->count() }} groups</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all="true">Select all</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-select-all="false">Clear all</button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                        <label class="input-group input-group-sm permission-search">
                            <span class="input-group-text bg-white"><i class="ri-search-line" aria-hidden="true"></i></span>
                            <input type="search" class="form-control" id="permissionSearch" placeholder="Find a permission…" aria-label="Find a permission">
                        </label>
                        <div class="small text-muted" aria-live="polite"><strong id="selectedPermissionCount">0</strong> selected</div>
                    </div>

                    @forelse($permissionGroups as $group => $perms)
                        <section class="permission-group" data-permission-group="{{ $group }}" aria-label="{{ ucwords(str_replace('_', ' ', $group)) }} permissions">
                            <header class="permission-group-head">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="permission-group-title">{{ ucwords(str_replace('_', ' ', $group)) }}</span>
                                    <span class="permission-group-count"><span data-group-selected>0</span> / {{ $perms->count() }}</span>
                                </div>
                                <div class="permission-group-actions">
                                    <button type="button" data-group-action="select">Select group</button>
                                    <button type="button" data-group-action="clear">Clear group</button>
                                </div>
                            </header>
                            <div class="permission-grid">
                                @foreach($perms as $perm)
                                    @php($isChecked = in_array($perm->id, old('permissions', $rolePermissions)))
                                    <label class="permission-option {{ $isChecked ? 'is-selected' : '' }}" data-permission-item data-search="{{ strtolower($perm->name) }}">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}" class="perm-check" data-group="{{ $group }}" {{ $isChecked ? 'checked' : '' }}>
                                        <span class="permission-check" aria-hidden="true"><i class="ri-check-line"></i></span>
                                        <span class="permission-copy">
                                            <strong>{{ ucwords(str_replace(['_', '-'], ' ', $perm->name)) }}</strong>
                                            <code>{{ $perm->name }}</code>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="text-center text-muted border rounded-3 p-4">No permissions have been configured yet.</div>
                    @endforelse

                    <div class="permission-savebar">
                        <span class="small text-muted">Changes apply to everyone assigned this role.</span>
                        <button type="submit" class="btn btn-dark btn-sm px-3">
                            <i class="ri-save-line me-1"></i> Save permissions
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="col-xl-4">
            <section class="assigned-panel" aria-labelledby="assignedUsersTitle">
                <div class="p-3 border-bottom">
                    <h2 id="assignedUsersTitle" class="h6 fw-bold mb-1">Assigned users</h2>
                    <div class="small text-muted">{{ $usersWithRole->count() }} account(s) use this role</div>
                </div>
                @if($usersWithRole->isEmpty())
                    <div class="p-4 text-center text-muted small">No users are assigned to this role.</div>
                @else
                    @foreach($usersWithRole as $user)
                        <div class="assigned-user">
                            <div class="min-w-0">
                                <span class="assigned-user-name">{{ $user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->surname ?? '')) ?: $user->email }}</span>
                                <span class="assigned-user-email">{{ $user->email }}</span>
                            </div>
                            <span class="badge bg-light text-dark border">{{ ucfirst($user->account_type ?? 'account') }}</span>
                        </div>
                    @endforeach
                @endif
            </section>
        </aside>
    </div>
</div>

<script>
(() => {
    const form = document.getElementById('permissionForm');
    if (!form) return;

    const checks = Array.from(form.querySelectorAll('.perm-check'));
    const count = document.getElementById('selectedPermissionCount');

    function refreshSelection() {
        checks.forEach((checkbox) => {
            checkbox.closest('.permission-option')?.classList.toggle('is-selected', checkbox.checked);
        });
        if (count) count.textContent = String(checks.filter((checkbox) => checkbox.checked).length);
        document.querySelectorAll('[data-permission-group]').forEach((group) => {
            const groupChecks = Array.from(group.querySelectorAll('.perm-check'));
            const selected = group.querySelector('[data-group-selected]');
            if (selected) selected.textContent = String(groupChecks.filter((checkbox) => checkbox.checked).length);
        });
    }

    form.addEventListener('change', (event) => {
        if (event.target.matches('.perm-check')) refreshSelection();
    });

    form.querySelectorAll('[data-select-all]').forEach((button) => {
        button.addEventListener('click', () => {
            checks.forEach((checkbox) => { checkbox.checked = button.dataset.selectAll === 'true'; });
            refreshSelection();
        });
    });

    form.querySelectorAll('[data-group-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.closest('[data-permission-group]');
            group?.querySelectorAll('.perm-check').forEach((checkbox) => {
                checkbox.checked = button.dataset.groupAction === 'select';
            });
            refreshSelection();
        });
    });

    const search = document.getElementById('permissionSearch');
    search?.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        document.querySelectorAll('[data-permission-group]').forEach((group) => {
            const items = Array.from(group.querySelectorAll('[data-permission-item]'));
            let visibleItems = 0;
            items.forEach((item) => {
                const visible = !query || item.dataset.search.includes(query);
                item.hidden = !visible;
                if (visible) visibleItems++;
            });
            group.hidden = visibleItems === 0;
        });
    });

    refreshSelection();
})();
</script>
@endsection
