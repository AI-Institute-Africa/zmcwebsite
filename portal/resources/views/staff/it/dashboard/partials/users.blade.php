<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0">User Directory</h6>
                <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">+ Add Service User</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0">
                        <thead class="bg-slate-50 border-top border-bottom border-slate-100">
                            <tr>
                                <th class="ps-4 small text-slate-700 uppercase fw-bold py-3">User</th>
                                <th class="small text-slate-700 uppercase fw-bold py-3">Roles</th>
                                <th class="small text-slate-700 uppercase fw-bold py-3">Status</th>
                                <th class="small text-slate-700 uppercase fw-bold py-3 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=f1f5f9&color=475569" class="rounded-circle" width="32">
                                        <div>
                                            <div class="fw-bold text-slate-900 small">{{ $user->name }}</div>
                                            <div class="text-slate-700 fw-medium" style="font-size: 11px;">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                    <span class="badge bg-slate-100 text-slate-800 border px-2 py-1 rounded-pill small fw-bold">{{ strtoupper(str_replace('_',' ', $role->name)) }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="badge {{ $user->account_status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-3">
                                        {{ strtoupper($user->account_status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                     <div class="dropdown">
                                        <button class="btn btn-sm btn-icon border-0 bg-transparent text-slate-400" data-bs-toggle="dropdown">
                                            <i class="ri-more-2-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border shadow-sm rounded-3">
                                            <li>
                                                <form action="{{ route('staff.it.user.reset_password', $user->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item small"><i class="ri-key-line me-2"></i> Force Password Reset</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="{{ route('staff.it.user.suspend', $user->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item small text-danger"><i class="ri-user-unfollow-line me-2"></i> {{ $user->account_status === 'suspended' ? 'Reactivate' : 'Suspend' }} Account Nav</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h6 class="fw-bold mb-4">RBAC Roles</h6>
            <div class="d-flex flex-column gap-3">
                @foreach($roles as $role)
                <div class="d-flex align-items-center justify-content-between p-3 rounded-4 bg-slate-50 border border-slate-100">
                    <div>
                        <div class="fw-bold text-slate-900 small">{{ strtoupper(str_replace('_',' ', $role->name)) }}</div>
                        <div class="text-slate-600 small fw-bold">{{ $role->users_count }} Members</div>
                    </div>
                    <button class="btn btn-sm btn-slate-200 border-0 rounded-pill px-3">Edit</button>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
