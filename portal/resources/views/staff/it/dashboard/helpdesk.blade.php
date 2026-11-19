@extends('layouts.portal')
@section('title', 'Helpdesk / Support - IT Admin')
@section('content')
<div class="it-dashboard" style="font-family:'Inter',sans-serif; color:#1e293b; background:#f8fafc; min-height:100vh; padding:20px;">

    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded-4 shadow-sm border">
        <div>
            <h2 class="fw-bold m-0" style="color:#0f172a;">Helpdesk / Support</h2>
            <p class="text-muted m-0 small mt-1">IT Admin support queue · assignment, progress, history, and email updates</p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-warning-subtle text-warning border px-3 py-2 rounded-pill fw-bold">{{ $openCount }} Open</span>
            <button class="btn btn-dark rounded-pill px-3 fw-bold small" data-bs-toggle="modal" data-bs-target="#newTicketModal"><i class="ri-add-line"></i> New Ticket</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success rounded-3">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="alert alert-warning rounded-3">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>@endif

    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="{{ route('staff.it.helpdesk') }}" class="btn btn-sm {{ !request('status') ? 'btn-dark' : 'btn-light border' }} rounded-pill px-3 fw-bold">All</a>
        @foreach(['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $k=>$v)
        <a href="{{ route('staff.it.helpdesk', ['status' => $k]) }}" class="btn btn-sm {{ request('status') === $k ? 'btn-dark' : 'btn-light border' }} rounded-pill px-3 fw-bold">{{ $v }}</a>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th class="ps-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Subject</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Category / Priority</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Status</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Submitted By</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Assigned To</th>
                        <th class="fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Created</th>
                        <th class="text-end pe-4 fw-semibold text-uppercase" style="font-size:11px; letter-spacing:.05em; color:#64748b;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td class="ps-4" id="ticket-{{ $t->id }}">
                            <div class="fw-bold" style="color:#334155;">{{ $t->subject }}</div>
                            @if($t->incident)<a class="small" href="{{ route('staff.it.incidents') }}">Monitoring incident #{{ $t->incident_id }}</a>@endif
                            @if($t->description)<div class="text-muted small">{{ Str::limit($t->description, 85) }}</div>@endif
                        </td>
                        <td>
                            @php $prioColor = match($t->priority) { 'critical' => 'danger', 'high' => 'warning', 'medium' => 'info', default => 'secondary' }; @endphp
                            <div class="small text-muted mb-1">{{ $t->category ?: 'Uncategorised' }}</div>
                            <span class="badge bg-{{ $prioColor }}-subtle text-{{ $prioColor }} border rounded-pill">{{ ucfirst($t->priority) }}</span>
                        </td>
                        <td>
                            @php $tStatColor = match($t->status) { 'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', default => 'secondary' }; @endphp
                            <span class="badge bg-{{ $tStatColor }}-subtle text-{{ $tStatColor }} border rounded-pill">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span>
                        </td>
                        <td class="text-muted fw-medium">{{ $t->submitter?->name ?? '-' }}</td>
                        <td class="text-muted fw-medium">{{ $t->assignee?->hasRole('it_admin') && !$t->assignee?->hasRole('super_admin') ? $t->assignee->name : 'Unassigned' }}</td>
                        <td class="text-muted">{{ $t->created_at->diffForHumans() }}</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#updateTicket{{ $t->id }}"><i class="ri-edit-line"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted fw-medium">No support tickets.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tickets->hasPages())
        <div class="p-4 border-top">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>

@foreach($tickets as $t)
<div class="modal fade" id="updateTicket{{ $t->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('staff.it.helpdesk.update', $t) }}" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold">Update: {{ Str::limit($t->subject, 40) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                @if($t->description)<div class="mb-3"><div class="small fw-bold text-muted mb-1">Description</div><div class="small">{{ $t->description }}</div></div>@endif
                @if($t->attachment_path)<div class="mb-3"><a href="{{ route('staff.it.helpdesk.attachment', $t) }}" class="btn btn-sm btn-light border rounded-pill"><i class="ri-attachment-line"></i> {{ $t->attachment_name ?: 'Download attachment' }}</a></div>@endif
                @if($t->updates->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Update history</label>
                        <div class="border rounded-3 p-2" style="max-height:190px; overflow:auto;">
                            @foreach($t->updates as $update)
                                <div class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="d-flex justify-content-between gap-2 small"><strong>{{ $update->user?->name ?? 'System' }}</strong><span class="text-muted">{{ $update->created_at->format('d M Y H:i') }}</span></div>
                                    @if($update->body)<div class="small mt-1">{{ $update->body }}</div>@endif
                                    @if($update->changes)
                                        <div class="small text-muted mt-1">@foreach($update->changes as $field => $change){{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $change['from'] ?: '—' }} → {{ $change['to'] ?: '—' }}@if(!$loop->last); @endif @endforeach</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Status</label>
                    <select name="status" class="form-select rounded-3">
                        @foreach(['open','in_progress','resolved','closed'] as $s)
                        <option value="{{ $s }}" {{ $t->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Assign To</label>
                    <select name="assigned_to" class="form-select rounded-3">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $su)
                        <option value="{{ $su->id }}" {{ $t->assignee?->hasRole('it_admin') && !$t->assignee?->hasRole('super_admin') && $t->assigned_to == $su->id ? 'selected' : '' }}>{{ $su->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-bold text-muted">Resolution Summary</label>
                    <textarea name="resolution_summary" class="form-control rounded-3" rows="3">{{ $t->resolution_summary }}</textarea>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Add update / comment</label>
                    <textarea name="comment" class="form-control rounded-3" rows="2" maxlength="5000" placeholder="Add a progress note for the ticket history and email update"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Update</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('staff.it.helpdesk.store') }}" method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow-lg rounded-4">
            @csrf
            <div class="modal-header border-0 p-4 pb-2">
                <h5 class="modal-title fw-bold">Create Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Subject</label>
                    <input type="text" name="subject" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Description</label>
                    <textarea name="description" class="form-control rounded-3" rows="3" maxlength="10000" required></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Priority</label>
                        <select name="priority" class="form-select rounded-3" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Category</label>
                        <select name="category" class="form-select rounded-3" required>
                            <option value="">Select category</option>
                            <option>System incident</option><option>Access and accounts</option><option>Website or portal</option><option>Payments</option><option>Documents and printing</option><option>Equipment</option><option>Other</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Assign To</label>
                    <select name="assigned_to" class="form-select rounded-3">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $su)
                        <option value="{{ $su->id }}">{{ $su->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3">
                    <label class="form-label small fw-bold text-muted">Attachment (optional)</label>
                    <input type="file" name="attachment" class="form-control rounded-3" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    <div class="form-text">PDF, image, Word, or Excel file; maximum 10 MB.</div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-2">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-dark rounded-pill px-4">Create Ticket</button>
            </div>
        </form>
    </div>
</div>
@endsection
