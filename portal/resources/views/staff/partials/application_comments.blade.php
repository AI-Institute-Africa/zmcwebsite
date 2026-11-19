<div class="card shadow-sm border-0 mb-4" id="commentsSection">
    <div class="card-header d-flex justify-content-between align-items-center" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
        <span class="fw-bold" style="color: #1e293b;">
            <i class="ri-discuss-line me-1"></i> Staff Comments
            @if(isset($application) && $application->comments_count !== null)
                <span class="badge bg-secondary ms-1">{{ $application->comments_count }}</span>
            @elseif(isset($comments))
                <span class="badge bg-secondary ms-1">{{ $comments->count() }}</span>
            @endif
        </span>
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addCommentForm">
            <i class="ri-add-line me-1"></i> Add Comment
        </button>
    </div>

    <div class="collapse" id="addCommentForm">
        <div class="card-body border-bottom" style="background: #fffbeb;">
            <form method="POST" action="{{ route('staff.applications.comment', $application) }}">
                @csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Comment Type</label>
                        <select name="comment_type" class="form-select form-select-sm" required>
                            @foreach(\App\Models\ApplicationComment::commentTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold mb-1">Visibility</label>
                        <select name="visibility" class="form-select form-select-sm">
                            <option value="internal">Internal (Staff Only)</option>
                            <option value="applicant">Visible to Applicant</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <textarea name="body" class="form-control form-control-sm" rows="3" required
                        placeholder="Enter your comment or notes about this application..." maxlength="5000"></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="ri-information-line me-1"></i>
                        Posting as <strong>{{ ucfirst(str_replace('_', ' ', session('active_staff_role', 'Staff'))) }}</strong>
                        &mdash; comments are audited and timestamped.
                    </small>
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="ri-send-plane-line me-1"></i> Post Comment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @php
            $displayComments = $comments ?? ($application->comments ?? collect());
        @endphp

        @forelse($displayComments as $comment)
            <div class="border-bottom px-3 py-3 {{ $loop->even ? '' : '' }}" style="background: {{ $loop->even ? '#fafbfc' : '#fff' }};">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-{{ $comment->getRoleBadgeColor() }} text-white" style="font-size: 0.7rem;">
                            {{ $comment->getRoleLabel() }}
                        </span>
                        <span class="badge bg-{{ $comment->getTypeBadgeColor() }}" style="font-size: 0.65rem;">
                            {{ $comment->getTypeLabel() }}
                        </span>
                        @if($comment->visibility === 'applicant')
                            <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">
                                <i class="ri-eye-line"></i> Visible to Applicant
                            </span>
                        @else
                            <span class="badge bg-light text-muted border" style="font-size: 0.6rem;">
                                <i class="ri-eye-off-line"></i> Internal
                            </span>
                        @endif
                    </div>
                    <small class="text-muted text-nowrap" style="font-size: 0.7rem;">
                        {{ $comment->created_at->format('d M Y, H:i') }}
                    </small>
                </div>
                <div class="mb-1" style="color: #334155; font-size: 0.9rem; line-height: 1.5;">
                    {!! nl2br(e($comment->body)) !!}
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="ri-user-3-line me-1"></i>{{ $comment->user?->name ?? 'System' }}
                    </small>
                    <small class="text-muted" style="font-size: 0.65rem;">
                        Status at time: <code class="text-muted">{{ str_replace('_', ' ', $comment->application_status_at_time ?? '-') }}</code>
                    </small>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-4">
                <i class="ri-chat-3-line" style="font-size: 2rem; opacity: 0.3;"></i>
                <div class="mt-2 small">No comments yet. Click "Add Comment" to post the first note.</div>
            </div>
        @endforelse
    </div>
</div>
